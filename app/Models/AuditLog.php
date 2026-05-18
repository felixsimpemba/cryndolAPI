<?php

namespace App\Models;

/**
 * @deprecated Use ActivityLog instead.
 */
class AuditLog extends ActivityLog
{
    protected $table = 'activity_logs';
}
