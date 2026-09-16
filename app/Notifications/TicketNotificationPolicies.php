<?php

namespace App\Notifications;

use App\Enums\TicketPriority;
use App\Exceptions\NoNotificationPolicy;
use Illuminate\Support\Str;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class TicketNotificationPolicies
{
    /** @var array<int, TicketNotificationPolicy>|null */
    private ?array $policies = null;

    public function for(TicketPriority $priority): TicketNotificationPolicy
    {
        foreach ($this->all() as $policy) {
            if ($policy->handles($priority)) {
                return $policy;
            }
        }

        throw NoNotificationPolicy::for($priority);
    }

    /**
     * @return array<int, TicketNotificationPolicy>
     */
    public function all(): array
    {
        return $this->policies ??= array_map(
            fn (string $class): TicketNotificationPolicy => app($class),
            $this->discover(),
        );
    }

    /**
     * @return array<int, class-string<TicketNotificationPolicy>>
     */
    private function discover(): array
    {
        $directory = app_path('Notifications/Policies');

        if (! is_dir($directory)) {
            return [];
        }

        $classes = [];

        foreach (Finder::create()->files()->in($directory)->name('*.php') as $file) {
            $class = $this->classFor($file);

            if (! is_subclass_of($class, TicketNotificationPolicy::class)) {
                continue;
            }

            if (! (new ReflectionClass($class))->isInstantiable()) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }

    /**
     * @return class-string
     */
    private function classFor(SplFileInfo $file): string
    {
        $relative = Str::of($file->getRealPath())
            ->after(realpath(app_path()).DIRECTORY_SEPARATOR)
            ->replace(['/', '\\'], '\\')
            ->beforeLast('.php');

        return 'App\\'.$relative;
    }
}
