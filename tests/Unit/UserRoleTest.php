<?php

namespace Tests\Unit;

use App\Enums\TableStatus;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_user_role_enum_has_expected_values(): void
    {
        $this->assertSame('admin', UserRole::ADMIN->value);
        $this->assertSame('waiter', UserRole::WAITER->value);
        $this->assertSame('kitchen', UserRole::KITCHEN->value);
        $this->assertSame('manager', UserRole::MANAGER->value);
    }

    public function test_user_role_enum_resolves_from_string(): void
    {
        $this->assertSame(UserRole::ADMIN, UserRole::from('admin'));
        $this->assertNull(UserRole::tryFrom('nonexistent'));
    }

    public function test_table_status_enum_has_expected_values(): void
    {
        $this->assertSame('free', TableStatus::FREE->value);
        $this->assertSame('occupied', TableStatus::OCCUPIED->value);
        $this->assertSame('reserved', TableStatus::RESERVED->value);
    }
}
