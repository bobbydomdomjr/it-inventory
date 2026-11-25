<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationSmokeTest extends TestCase
{
    public function test_laravel_health_endpoint_returns_success(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_login_screen_renders_without_authentication(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to your workspace');
    }

    public function test_required_inventory_workflows_are_registered(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())->map(fn ($route) => $route->getName())->filter()->all();

        foreach ([
            'inventory.index',
            'assets.assign',
            'assignments.return',
            'assets.transfer',
            'consumables.movement',
            'licenses.assign',
            'licenses.return',
            'maintenance.complete',
            'maintenance.cancel',
            'audits.verify',
            'audits.complete',
            'procurements.order',
            'procurements.receive',
            'procurements.assets.store',
            'approvals.submit',
            'approvals.decide',
            'admin.offices',
            'admin.offices.store',
            'admin.offices.update',
            'admin.offices.destroy',
            'admin.approval-flows',
            'admin.approval-flows.store',
            'admin.approval-flows.steps.store',
            'documents.store',
            'reports.assets.csv',
        ] as $requiredRoute) {
            $this->assertContains($requiredRoute, $routes, "Missing required route: {$requiredRoute}");
        }
    }
}
