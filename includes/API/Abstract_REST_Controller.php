<?php

namespace BlockLigatures\API;

use BlockLigatures\DB\Repos\Abstract_Repo;

abstract class Abstract_REST_Controller {
    protected const API_VERSION = 'v1';
    protected const API_NAMESPACE = 'blig';
    protected string $namespace;
    protected Abstract_Repo $repo;

    public function __construct(Abstract_Repo $repo) {
        $this->namespace = self::API_NAMESPACE . '/' . self::API_VERSION;
        $this->repo = $repo;
        $this->init();
    }

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        foreach ( $this->get_routes_definitions() as $definition ) {
            $args = array(
                'methods'              => $definition['methods'],
                'callback'             => $definition['callback'],
                'permissions_callback' => $definition['permissions_callback'],
            );

            if ( isset( $definition['args'] ) ) {
                $args['args'] = $definition['args'];
            }

            register_rest_route( $this->namespace, $definition['route'], $args );
        }
    }
    abstract protected function get_routes_definitions(): array;
}