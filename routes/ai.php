<?php

use App\Mcp\Servers\SupportServer;
use Laravel\Mcp\Facades\Mcp;

/**
 * The agent reaches the domain as a real person.
 *
 * The bearer token belongs to a user, so every permission and every visibility
 * scope written for the interface applies unchanged: an agent authenticated as a
 * requester sees that requester's tickets and nothing else.
 */
Mcp::web('mcp/support', SupportServer::class)->middleware('auth:sanctum');

Mcp::local('support', SupportServer::class);
