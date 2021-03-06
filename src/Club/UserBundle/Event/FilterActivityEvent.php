<?php

namespace Club\UserBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FilterActivityEvent extends Event
{
    protected $activities = array();
    protected $user;

    public function __construct(\Club\UserBundle\Entity\User $user)
    {
        $this->user = $user;
    }

    public function appendActivities($activity, $key)
    {
        if (isset($this->activities[$key])) {
            $key++;
            $this->appendActivities($activity, $key);
        } else {
            $this->activities[$key] = $activity;
        }
    }

    public function setActivities($activities)
    {
        $this->activities = $activities;
    }

    public function getActivities()
    {
        krsort($this->activities);
        return $this->activities;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
