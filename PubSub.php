<?php
namespace Util;

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
     */
    function unsubscribeByKey(string $key) {
        foreach ($this->actions as $action => $entry) {
            foreach ($entry as $id => $subscription) {
                if ($key == $id) {
                    unset($this->actions[$action][$key]);
                    break 2;
                }
            }
        }
    }

    /**
     * @param callable $fn
     * @return NULL|string
     */
    function unsubscribe(callable $fn) {
        $id = null;
        foreach ($this->actions as $action => $entry) {
            foreach ($entry as $key => $callBack) {
                if ($fn == $callBack) {
                    unset($this->actions[$action][$key]);
                    $id = $key;
                    break 2;
                }
            }
        }
        return $id;
    }

    /**
     *
     */
    function clear() {
        $this->actions = [];
    }
}
