<?php

namespace SyntaxError\NotificationBundle\Kernel;


class Storage
{
    /**
     * Path to storage file.
     *
     * @var string
     */
    private $file;

    /**
     * Array of subscribers emails [emails] and array of locked notifies [locked].
     *
     * @var array
     */
    private $data = [];

    /**
     * RedisStorage constructor.
     */
    public function __construct()
    {
        $this->file = __DIR__."/../../../../app/cache/subscribers.json";
        if(!file_exists($this->file)) file_put_contents($this->file, '{}');
        $this->data = json_decode(file_get_contents($this->file));
        if(!property_exists($this->data, 'emails')) $this->data->emails = [];
        if(!property_exists($this->data, 'locked')) $this->data->locked = new \stdClass;
    }

    /**
     * Save data to file.
     *
     * @return Storage
     */
    private function save()
    {
        file_put_contents($this->file, json_encode($this->data));
        return $this;
    }

    /**
     * Check notify is unlocked for sending.
     *
     * @param $notifyName
     * @return bool
     */
    public function isLocked($notifyName)
    {
        if(!property_exists($this->data->locked, $notifyName)) return false;
        if($this->data->locked->{$notifyName} <= time()+3600*24) {
            $returned = true;
        } else {
            $returned = false;
            unset($this->data->locked->{$notifyName});
            $this->save();
        }
        return $returned;
    }

    /**
     * Lock notify on 24 hours.
     *
     * @param $notifyName
     * @return bool
     */
    public function lock($notifyName)
    {
        $this->data->locked->{$notifyName} = time();
        $this->save();
        return true;
    }

    /**
     * Return array of subscribers emails.
     *
     * @return array
     */
    public function getSubscribers()
    {
        return $this->data->emails;
    }

    /**
     * Add subscriber to Redis.
     *
     * @param $subscriber
     * @return bool
     */
    public function addSubscriber($subscriber)
    {
        if(in_array($subscriber, $this->data->emails)) return false;
        $this->data->emails[] = $subscriber;
        $this->save();
        return true;
    }

    /**
     * Remove subscriber from Redis.
     *
     * @param $subscriber
     * @return bool
     */
    public function removeSubscriber($subscriber)
    {
        $found = false;
        foreach($this->data->emails as $i => $registeredSubscriber) {
            if($subscriber == $registeredSubscriber) {
                unset($this->data->emails[$i]);
                $found = true;
            }
        }
        $this->save();
        return $found;
    }

    /**
     * @param $subscriber
     * @return bool
     */
    public function hasSubscriber($subscriber)
    {
        return in_array($subscriber, $this->getSubscribers());
    }
}
