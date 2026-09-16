<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Exceptions\UnreadableImportFile;
use App\Imports\ImportRow;
use App\Imports\Steps\NormaliseRow;
use App\Imports\Steps\ResolveRequester;
use App\Imports\Steps\ValidateRow;
use App\Imports\TicketImporter;
use App\Jobs\ImportTicketsFromCsv;
use App\Livewire\Tickets\Import;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketImportFinished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketImportTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Notification::fake();
    }

    private function csv(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
        file_put_contents($path, $body);

        return $path;
    }

    public function test_a_clean_file_creates_every_ticket(): void
    {
        $requester = $this->requester();

        $report = app(TicketImporter::class)->import($this->csv(<<<CSV
        email,title,description,priority
        {$requester->email},Imprimante hors service,Bourrage papier fantome au deuxieme etage,high
        {$requester->email},VPN inaccessible,Le client renvoie une erreur 812 depuis ce matin,critical
        CSV));

        $this->assertSame(2, $report->read);
        $this->assertSame(2, $report->created);
        $this->assertTrue($report->isClean());
        $this->assertSame(2, Ticket::count());

        $ticket = Ticket::where('title', 'VPN inaccessible')->sole();

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame(TicketPriority::Critical, $ticket->priority);
        $this->assertSame($requester->getKey(), $ticket->requester_id);
        $this->assertMatchesRegularExpression('/^TCK-\d{5}$/', $ticket->reference);
    }

    public function test_an_imported_ticket_opens_its_timeline(): void
    {
        $requester = $this->requester();

        app(TicketImporter::class)->import($this->csv(<<<CSV
        email,title,description,priority
        {$requester->email},Imprimante hors service,Bourrage papier fantome au deuxieme etage,high
        CSV));

        $ticket = Ticket::sole();

        $this->assertSame(1, $ticket->statusChanges()->count());
        $this->assertSame(TicketStatus::Open, $ticket->statusChanges()->sole()->to_status);
    }

    public function test_invalid_rows_are_reported_with_their_line_number_and_the_rest_is_created(): void
    {
        $requester = $this->requester();

        $report = app(TicketImporter::class)->import($this->csv(<<<CSV
        email,title,description,priority
        {$requester->email},Imprimante hors service,Bourrage papier fantome au deuxieme etage,high
        pas-un-email,Ecran noir au demarrage,Le poste ne depasse pas le logo constructeur,normal
        {$requester->email},bug,Une description parfaitement valable et assez longue,normal
        {$requester->email},Casque non detecte,court,normal
        {$requester->email},Badge refuse,Le lecteur clignote rouge a chaque passage,urgentissime
        inconnu@support.test,Souris HS,Le capteur ne repond plus du tout depuis hier,low
        {$requester->email},Ecran bleu recurrent,Trois plantages depuis le debut de la semaine,low
        CSV));

        $this->assertSame(7, $report->read);
        $this->assertSame(2, $report->created);
        $this->assertSame(5, $report->rejectedCount());
        $this->assertSame(2, Ticket::count());

        $this->assertSame(
            [3, 4, 5, 6, 7],
            array_column($report->rejections, 'line'),
        );
    }

    public function test_an_unknown_requester_is_named_in_the_rejection(): void
    {
        $report = app(TicketImporter::class)->import($this->csv(<<<'CSV'
        email,title,description,priority
        fantome@support.test,Souris HS,Le capteur ne repond plus du tout depuis hier,low
        CSV));

        $this->assertStringContainsString('fantome@support.test', $report->rejections[0]['reason']);
    }

    public function test_a_file_without_the_expected_columns_stops_the_import(): void
    {
        $this->expectException(UnreadableImportFile::class);

        app(TicketImporter::class)->import($this->csv("objet,corps\nUn,Deux"));
    }

    public function test_a_missing_file_stops_the_import(): void
    {
        $this->expectException(UnreadableImportFile::class);

        app(TicketImporter::class)->import('/tmp/ce-fichier-nexiste-pas-du-tout.csv');
    }

    public function test_a_technical_failure_creates_nothing_at_all(): void
    {
        $this->requester();

        try {
            app(TicketImporter::class)->import($this->csv("objet,corps\nUn,Deux"));
        } catch (UnreadableImportFile) {
            // expected
        }

        $this->assertSame(0, Ticket::count());
    }

    public function test_the_reference_lookup_does_not_grow_with_the_number_of_rows(): void
    {
        $requester = $this->requester();

        $few = $this->countResolutionQueries($requester, 3);
        $many = $this->countResolutionQueries($requester, 200);

        $this->assertSame($few, $many, "La resolution passe de {$few} a {$many} requetes.");
        $this->assertSame(1, $many);
    }

    public function test_the_whole_import_is_a_handful_of_queries_whatever_the_size(): void
    {
        $requester = $this->requester();

        $few = $this->countImportQueries($requester, 5);
        $many = $this->countImportQueries($requester, 150);

        $this->assertSame($few, $many, "L'import passe de {$few} a {$many} requetes.");
    }

    public function test_the_normalise_step_trims_and_lowercases(): void
    {
        $rows = (new NormaliseRow)->handle([
            new ImportRow(2, ['email' => '  MAXIME@Support.TEST ', 'title' => "  Un   objet  \n", 'priority' => 'HIGH']),
        ]);

        $this->assertSame('maxime@support.test', $rows[0]->get('email'));
        $this->assertSame('Un objet', $rows[0]->get('title'));
        $this->assertSame('high', $rows[0]->get('priority'));
    }

    public function test_the_validate_step_rejects_on_its_own(): void
    {
        $rows = (new ValidateRow)->handle([
            new ImportRow(2, ['email' => 'a@b.test', 'title' => 'x', 'description' => 'y', 'priority' => 'low']),
        ]);

        $this->assertTrue($rows[0]->isRejected());
    }

    public function test_the_resolve_step_skips_rows_already_rejected(): void
    {
        $row = new ImportRow(2, ['email' => 'inconnu@support.test']);
        $row->reject('déjà rejetée');

        $rows = (new ResolveRequester)->handle([$row]);

        $this->assertSame('déjà rejetée', $rows[0]->rejection);
    }

    public function test_the_upload_queues_the_job(): void
    {
        Queue::fake();
        Storage::fake('local');

        Livewire::actingAs($this->manager())
            ->test(Import::class)
            ->set('file', UploadedFile::fake()->createWithContent('tickets.csv', "email,title,description,priority\n"))
            ->call('save')
            ->assertHasNoErrors();

        Queue::assertPushed(ImportTicketsFromCsv::class);
    }

    public function test_a_requester_cannot_reach_the_import_screen(): void
    {
        Livewire::actingAs($this->requester())
            ->test(Import::class)
            ->assertForbidden();
    }

    public function test_the_upload_refuses_anything_but_a_csv(): void
    {
        Storage::fake('local');

        Livewire::actingAs($this->manager())
            ->test(Import::class)
            ->set('file', UploadedFile::fake()->image('capture.png'))
            ->call('save')
            ->assertHasErrors('file');
    }

    public function test_the_operator_gets_the_report(): void
    {
        Storage::fake('local');

        $manager = $this->manager();
        $requester = $this->requester();

        $path = 'imports/tickets.csv';
        Storage::disk('local')->put($path, <<<CSV
        email,title,description,priority
        {$requester->email},Imprimante hors service,Bourrage papier fantome au deuxieme etage,high
        CSV);

        (new ImportTicketsFromCsv($path, $manager->getKey()))->handle(app(TicketImporter::class));

        Notification::assertSentTo($manager, TicketImportFinished::class);
        Storage::disk('local')->assertMissing($path);
    }

    private function countResolutionQueries(User $requester, int $rows): int
    {
        $lines = [];

        for ($index = 0; $index < $rows; $index++) {
            $lines[] = new ImportRow($index + 2, ['email' => $requester->email]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        (new ResolveRequester)->handle($lines);

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function countImportQueries(User $requester, int $rows): int
    {
        $body = "email,title,description,priority\n";

        for ($index = 0; $index < $rows; $index++) {
            $body .= "{$requester->email},Un objet parfaitement valable,Une description bien assez longue pour passer,normal\n";
        }

        $path = $this->csv($body);

        DB::flushQueryLog();
        DB::enableQueryLog();

        app(TicketImporter::class)->import($path);

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
