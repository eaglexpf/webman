<?php

namespace app\jobs;

class Job
{
    protected $num = 0;

    protected $retry_time = [15,15,30,180,600,1200,1800,1800,1800,3600,10800,10800,10800,21600,21600];

    public function retry()
    {
        $this->num++;
        if ($this->num > count($this->retry_time)) {
            return true;
        }
        sendJob($this)->setTtl($this->retry_time[$this->num - 1])->send();
        return true;
    }

}