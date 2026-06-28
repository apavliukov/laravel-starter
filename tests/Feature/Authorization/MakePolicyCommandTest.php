<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Authorization\Console\MakePolicyCommand;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(MakePolicyCommand::class)]
final class MakePolicyCommandTest extends TestCase
{
    private string $generatedPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatedPath = app_path('Policies/WidgetPolicy.php');

        if (File::exists($this->generatedPath)) {
            File::delete($this->generatedPath);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->generatedPath)) {
            File::delete($this->generatedPath);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_generates_a_policy_extending_the_abstract_policy(): void
    {
        $this->artisan('make:authorization-policy', ['model' => 'Widget'])
            ->assertExitCode(0);

        $this->assertTrue(File::exists($this->generatedPath));

        $contents = File::get($this->generatedPath);

        $this->assertStringContainsString('namespace App\Policies;', $contents);
        $this->assertStringContainsString('use App\Authorization\AbstractPolicy;', $contents);
        $this->assertStringContainsString('class WidgetPolicy extends AbstractPolicy', $contents);
        $this->assertStringContainsString('return Widget::class;', $contents);
    }
}
