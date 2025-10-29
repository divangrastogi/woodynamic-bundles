<?php
/**
 * Loader class for WooDynamic Bundles
 *
 * Handles autoloading and dependency management
 *
 * @package WooDynamic\Bundles
 */

namespace WooDynamic\Bundles;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loader class
 */
class Loader {

    /**
     * Array of actions to be registered
     *
     * @var array
     */
    protected $actions;

    /**
     * Array of filters to be registered
     *
     * @var array
     */
    protected $filters;

    /**
     * Array of shortcodes to be registered
     *
     * @var array
     */
    protected $shortcodes;

    /**
     * Initialize the collections
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
    }

    /**
     * Add a new action to the collection
     *
     * @param string $hook          The name of the WordPress action
     * @param object $component     A reference to the instance of the object on which the action is defined
     * @param string $callback      The name of the function definition on the $component
     * @param int    $priority      The priority at which the function should be fired
     * @param int    $accepted_args The number of arguments that should be passed to the $callback
     */
    public function woodynamic_add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->woodynamic_add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection
     *
     * @param string $hook          The name of the WordPress filter
     * @param object $component     A reference to the instance of the object on which the filter is defined
     * @param string $callback      The name of the function definition on the $component
     * @param int    $priority      The priority at which the function should be fired
     * @param int    $accepted_args The number of arguments that should be passed to the $callback
     */
    public function woodynamic_add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->woodynamic_add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new shortcode to the collection
     *
     * @param string $tag       The name of the shortcode
     * @param object $component A reference to the instance of the object on which the shortcode is defined
     * @param string $callback  The name of the function definition on the $component
     */
    public function woodynamic_add_shortcode($tag, $component, $callback) {
        $this->shortcodes[] = array(
            'tag'       => $tag,
            'component' => $component,
            'callback'  => $callback,
        );
    }

    /**
     * A utility function that is used to register the actions and hooks into a single collection
     *
     * @param array  $hooks         The collection of hooks that is being registered (actions or filters)
     * @param string $hook          The name of the WordPress filter
     * @param object $component     A reference to the instance of the object on which the filter is defined
     * @param string $callback      The name of the function definition on the $component
     * @param int    $priority      The priority at which the function should be fired
     * @param int    $accepted_args The number of arguments that should be passed to the $callback
     *
     * @return array The collection of actions and filters registered with WordPress
     */
    private function woodynamic_add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;
    }

    /**
     * Register the filters and actions with WordPress
     */
    public function woodynamic_run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->shortcodes as $shortcode) {
            add_shortcode(
                $shortcode['tag'],
                array($shortcode['component'], $shortcode['callback'])
            );
        }
    }
}
