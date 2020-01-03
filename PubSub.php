<?php

class PubSub {

    /**
     * @var array[]
     */
    private $actions = [];

    /**
     * @param string $action
     * @param callable $fn
     * @return string
     */
    function subscribe(string $action, callable $fn) {
        if (!isset($this->actions[$action])) {
            $this->actions[$action] = [];
        }
        $key = uniqid();
        $this->actions[$action][$key] = $fn;
        return $key;
    }

    /**
     * @param string $action
     * @param callable $fn
     * @return string
     */
    function subscribeOnce(string $action, callable $fn) {
        $key = "";
        $once = function($data) use ($fn, &$key) {
            $fn($data);
            $this->unsubscribeByKey($key);
        };
        $key = $this->subscribe($action, $once);
        return $key;
    }

    /**
     * @param string $action
     * @param mixed $data
     * @return string[]
     */
    function publish(string $action, $data) {
        $keys = [];
        if (isset($this->actions[$action])) {
            foreach ($this->actions[$action] as $key => $subscription) {
                $keys[] = $key;
                $subscription($data);
            }
        }
        return $keys;
    }

    /**
     * @param string $key
     * @return boolean
     */
    function unsubscribeByKey(string $key) {
        foreach ($this->actions as $action => $entry) {
            foreach ($entry as $id => $subscription) {
                if ($key == $id) {
                    unset($this->actions[$action][$key]);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param callable $fn
     * @return array
     */
    function unsubscribe(callable $fn) {
        $ids = [];
        foreach ($this->actions as $action => $entry) {
            foreach ($entry as $key => $callBack) {
                if ($fn == $callBack) {
                    unset($this->actions[$action][$key]);
                    $ids[] = $key;
                }
            }
        }
        return $ids;
    }

    /**
     *
     */
    function clear() {
        $this->actions = [];
    }
    
    /**
     * @param string $action
     * @return int
     */
    function count(string $action = "") {
        if(!empty($action)) {
            return isset($this->actions[$action]) ? count($this->actions[$action]) : 0;
        } else {
            $count = 0;
            foreach ($this->actions as $action => $entry) {
                $count += count($this->actions[$action]);
            }
            return $count;
        }
    }
    
    /**
     * @param string $key
     * @return boolean
     */
    function keyExists(string $key) {
        foreach ($this->actions as $action => $entry) {
            foreach ($entry as $id => $callBack) {
                if ($id == $key) {
                    return true;
                }
            }
        }
        return false;
    }
}
