<?php

namespace Bachelor\Port\Secondary\Database\NotificationManagement\Sms\Traits;

use Bachelor\Port\Secondary\Database\NotificationManagement\Notification\ModelDao\Notification;
use Bachelor\Port\Secondary\Database\UserManagement\User\ModelDao\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait SmsNotificationRelationshipTrait
{
    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class );
    }

    /**
     * @return BelongsTo
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class, 'key', 'key');
    }
}
