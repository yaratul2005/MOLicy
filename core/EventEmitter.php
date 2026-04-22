<?php

namespace Core;

class EventEmitter {
    private static array $actions = [];
    private static array $filters = [];

    /**
     * Register an action hook callback.
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10): void {
        self::$actions[$hook][$priority][] = $callback;
    }

    /**
     * Fire an action hook.
     */
    public static function doAction(string $hook, mixed ...$args): void {
        if (empty(self::$actions[$hook])) return;
        ksort(self::$actions[$hook]);
        foreach (self::$actions[$hook] as $callbacks) {
            foreach ($callbacks as $cb) {
                call_user_func_array($cb, $args);
            }
        }
    }

    /**
     * Register a filter hook callback.
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10): void {
        self::$filters[$hook][$priority][] = $callback;
    }

    /**
     * Apply filter hooks to a value.
     */
    public static function applyFilters(string $hook, mixed $value, mixed ...$args): mixed {
        if (empty(self::$filters[$hook])) return $value;
        ksort(self::$filters[$hook]);
        foreach (self::$filters[$hook] as $callbacks) {
            foreach ($callbacks as $cb) {
                $value = call_user_func_array($cb, array_merge([$value], $args));
            }
        }
        return $value;
    }

    /**
     * Check if an action hook has been registered.
     */
    public static function hasAction(string $hook): bool {
        return !empty(self::$actions[$hook]);
    }

    /**
     * Remove a specific action callback.
     */
    public static function removeAction(string $hook, callable $callback, int $priority = 10): void {
        if (isset(self::$actions[$hook][$priority])) {
            self::$actions[$hook][$priority] = array_filter(
                self::$actions[$hook][$priority],
                fn($cb) => $cb !== $callback
            );
        }
    }
}

// Global convenience aliases
function do_action(string $hook, mixed ...$args): void {
    EventEmitter::doAction($hook, ...$args);
}
function add_action(string $hook, callable $cb, int $priority = 10): void {
    EventEmitter::addAction($hook, $cb, $priority);
}
function apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
    return EventEmitter::applyFilters($hook, $value, ...$args);
}
function add_filter(string $hook, callable $cb, int $priority = 10): void {
    EventEmitter::addFilter($hook, $cb, $priority);
}
