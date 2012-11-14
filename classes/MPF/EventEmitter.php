<?php
namespace MPF;

class EventEmitter {
    protected $listeners = array();

    public function on($event, $callback) {
        if (!array_key_exists($event, $this->listeners) || !is_array($this->listeners[ $event ])) {
            $this->listeners[ $event ] = array();
        }

        $this->listeners[ $event ][] = $callback;
    }

    public function emit($event) {
        $args = array_splice(func_get_args(), 1);
        foreach ($this->listeners as $evt => $callbacks) {
            if ($event == $evt || $this->isRegexMatch($event, $evt)) {
                foreach ($callbacks as $callback) {
                    // if its a reference to a callback of an object and it as more than 2 values it means we are trying to
                    // pass extra args to the callback
                    if (is_array($callback) && count($callback) > 2) {
                        $args = array_merge($args, array_splice($callback, 2));
                    }

                    call_user_func_array($callback, $args);
                }
            }
        }
    }

    public function remove($event) {
        foreach ($this->listeners as $evt => $callbacks) {
            if ($event == $evt || $this->isRegexMatch($event, $evt)) {
                $this->listeners[ $evt ] = null;
                unset($this->listeners[ $evt ]);
            }
        }
    }

    protected function isRegexMatch($event, $regex) {
        if ($this->isRegexValid($regex) && preg_match($regex, $event)) {
            return true;
        }
        return false;
    }

    final private function isRegexValid($regex) {
        ini_set('track_errors', 'on');
        $php_errormsg = '';
        @preg_match($regex, '');
        ini_set('track_errors', 'off');

        if($php_errormsg) {
            return false;
        }
        return true;
    }
}