<?php

namespace Bachelor\Port\Secondary\Database\NotificationManagement\Sms\ModelDao;

use Bachelor\Domain\NotificationManagement\Sms\Models\NotificationSmsMessage as SmsNotificationDomainModel;
use Bachelor\Port\Secondary\Database\Base\BaseModel;
use Bachelor\Port\Secondary\Database\NotificationManagement\Sms\Traits\SmsNotificationRelationshipTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationSmsMessage extends BaseModel
{
    use HasFactory, SmsNotificationRelationshipTrait;

    /**
     * @var string
     */
    protected $table = 'notification_sms_messages';

    /**
     * @return SmsNotificationDomainModel
     */
    public function toDomainEntity ()
    {
        $model = new SmsNotificationDomainModel(
            $this->user_id,
            $this->key,
            $this->title,
            $this->content,
            $this->status,
            $this->notification_id
        );
        $model->setId($this->getKey());

        return $model;
    }

    /**
     * @param SmsNotificationDomainModel $model
     * @return NotificationSmsMessage
     */
    protected function fromDomainEntity($model)
    {
        $this->user_id = $model->getUserId();
        $this->key = $model->getKey();
        $this->title = $model->getTitle();
        $this->content = $model->getContent();
        $this->status = $model->getStatus();
        $this->notification_id = $model->getNotificationId();

        return $this;
    }
}
