<?php

/**
 * Gestion de visualización de solicitudes en dashboard admin.
 */

class Roles_Permissions_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_title    The ID of this plugin.
     */
    private $plugin_title;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /***************************** CLASS ****************************/
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_title       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_title, $version)
    {
        $this->plugin_title = $plugin_title;
        $this->version = $version;
    }

    /************************** ACTIONS METHODS HOOKED */

    /**
     * is user site_manager ?
     */
    public function is_site_manager(): bool
    {
        return current_user_can('site_manager');
    }

    /**
     * is user content_manager ?
     */
    public function is_content_manager(): bool
    {
        return current_user_can('content_manager');
    }

    /**
     * is user custom role (site_manager or content_manager) ?
     */
    public function is_custom_role(): bool
    {
        return $this->is_site_manager() || $this->is_content_manager();
    }

    /**
     *  Restrict dashboard access for custom roles.
     */
    public function restrict_dashboard_access(): void
    {
        //content_manager SCREENS IDS:
        // IDs: media, upload. edit-solicitud_producto, solicitud_producto, edit-product, product, profile

        //site_manager
        // IDs: users, user, user-edit

        $allowed_ids_content_manager = [
            'media', // Media Library
            'upload', // Add New Media
            'edit-solicitud_producto', // Solicitudes de producto
            'solicitud_producto', // Editar solicitud de producto
            'edit-product', // Productos WooCommerce
            'product', // Editar producto WooCommerce
            'profile', // Perfil usuario
            'edit-product_brand', // Marcas de producto
            // 'edit-product_cat', // Categorías de producto
        ];

        $screen = get_current_screen();
        // error_log('Current screen ID: ' . $screen->id);

        if ($this->is_content_manager()) {

            if (!in_array($screen->id, $allowed_ids_content_manager, true)) {
                wp_redirect(admin_url('edit.php?post_type=product'));
                exit;
            }
        }

        $allowed_ids_site_manager = [
            'user', // Gestión de usuarios
            'users', // Añadir nuevo usuario
            'user-edit', // Editar usuario
        ];
        $allowed_ids_site_manager = array_merge($allowed_ids_content_manager, $allowed_ids_site_manager);

        if ($this->is_site_manager()) {

            if (!in_array($screen->id, $allowed_ids_site_manager, true)) {
                wp_redirect(admin_url('edit.php?post_type=product'));
                exit;
            }
        }
    }


    /**
     * Filtra la lista de usuarios para que site_manager solo vea content_manager
     */
    public function filter_users_list_for_site_manager($query)
    {
        if ($this->is_site_manager() && is_admin() && isset($_GET['page']) === false) {
            $query->set('role', 'content_manager');
        }
    }

    /**
     * Solo permite asignar el rol content_manager al crear/editar usuarios
     */
    public function filter_editable_roles_for_site_manager($roles)
    {
        if ($this->is_site_manager()) {
            return ['content_manager' => $roles['content_manager']];
        }
        return $roles;
    }

    /**
     * Fuerza que los nuevos usuarios creados por site_manager sean content_manager
     */
    public function force_content_manager_role_on_create($user_id)
    {
        if ($this->is_site_manager()) {
            $user = get_userdata($user_id);
            if ($user && !in_array('content_manager', (array)$user->roles, true)) {
                $user->set_role('content_manager');
            }
        }
    }

    /**
     * Remueve los links de filtro por rol en la lista de usuarios para site_manager
     */
    public function remove_role_filters_for_site_manager($views)
    {
        if ($this->is_custom_role()) {
            return [];
        }
    }

    /**
     * Register custom roles and their capabilities.
     */
    public function register_roles(): void
    {
        $default_caps = [
            // WP Core y acceso backend
            'read',
            'upload_files',
            'edit_posts',
            'edit_pages',
            'edit_others_posts',
            'edit_others_pages',
            'edit_published_posts',
            'edit_published_pages',
            'publish_posts',
            'publish_pages',
            'delete_posts',
            'delete_pages',
            'delete_others_posts',
            'delete_others_pages',
            'delete_published_posts',
            'delete_published_pages',
            'read_private_posts',
            'read_private_pages',
            'edit_private_posts',
            'edit_private_pages',
            'delete_private_posts',
            'delete_private_pages',
            'manage_categories',
            'moderate_comments',
            'edit_theme_options',
            'list_users',
            // Niveles
            'level_0',
            'level_1',
            'level_2',
            'level_3',
            'level_4',
            'level_5',
            'level_6',
            'level_7',
            'level_8',
            'level_9',

            // WooCommerce Products
            'edit_products',
            'edit_product',
            'edit_others_products',
            'edit_published_products',
            'publish_products',
            'read_product',
            //marcas
            'manage_product_terms',
            'edit_product_terms',
            'delete_product_terms',
            'assign_product_terms',

            // Plugin Solicitudes
            'manage_requests',

            //capacidad de alta/baja/edit las categorias cuando se edita un producto
            'assign_product_cat',
            'edit_product_cat',
            'delete_product_cat',

        ];

        remove_role('content_manager');
        remove_role('site_manager');
        // ===== Content Manager =====
        if (! get_role('content_manager')) {
            add_role(
                'content_manager',
                __('Gestor - AGRO51', 'roles-permissions'),
                ['read' => true]
            );
        }

        $content_manager = get_role('content_manager');

        if ($content_manager) {
            $caps = $default_caps;

            foreach ($caps as $cap) {
                $content_manager->add_cap($cap);
            }
        }

        // ===== Site Manager =====
        if (! get_role('site_manager')) {
            add_role(
                'site_manager',
                __('Admin - AGRO51', 'roles-permissions'),
                ['read' => true]
            );
        }

        $site_manager = get_role('site_manager');

        if ($site_manager) {
            $caps = $default_caps;
               
            $site_caps = [
                // gestión de usuarios (limitada luego)
                'create_users',
                'edit_users',
            ];

            foreach ($caps as $cap) {
                $site_manager->add_cap($cap);
            }
        }
    }

    /**
     * Encola los assets del plugin solo para los roles personalizados
     */
    public function enqueue_roles_permissions_assets()
    {
        if (is_user_logged_in() && $this->is_custom_role()) {
            wp_enqueue_style(
                'roles-permissions-admin-css',
                plugins_url('/css/roles-permissions-admin.css', __FILE__),
                array(),
                $this->version
            );
        }
    }

    /**
     * Imprime el CSS de ocultar Elementor en el footer del frontend para máxima prioridad
     */
    public function print_roles_permissions_css_footer()
    {
        if ($this->is_custom_role()) {
            echo '<style>#wpadminbar #wp-admin-bar-elementor_edit_page, #wpadminbar li#wp-admin-bar-elementor_edit_page, #wpadminbar #wp-admin-bar-elementor_edit_page-default, #wpadminbar [id^="wp-admin-bar-elementor_"], #wpadminbar #wp-admin-bar-elementor_edit_page .ab-item, #wpadminbar #wp-admin-bar-elementor_edit_page .ab-sub-wrapper, #wpadminbar #wp-admin-bar-elementor_edit_page .ab-submenu, #wpadminbar #wp-admin-bar-elementor_edit_page *, #wpadminbar li[id*="elementor"] {display:none!important;visibility:hidden!important;max-height:0!important;height:0!important;overflow:hidden!important;}</style>';
        }
    }

    /**
     * Callback para filtrar los roles editables.
     * Se registra desde Main.php con el hook 'editable_roles'.
     */
    public function filter_editable_roles($roles)
    {
        if (current_user_can('administrator')) {
            return $roles;
        }

        if (current_user_can('site_manager')) {
            return [
                'content_manager' => $roles['content_manager'],
            ];
        }
        return $roles;
    }

    /**
     * Callback para mapear capacidades meta de usuario.
     * Se registra desde Main.php con el hook 'map_meta_cap'.
     */
    public function filter_map_meta_cap($caps, $cap, $user_id)
    {
        if (!in_array($cap, ['promote_user', 'delete_user', 'remove_user'], true)) {
            return $caps;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return $caps;
        }
        if (in_array('administrator', $user->roles, true)) {
            return $caps;
        }
        if (in_array('site_manager', $user->roles, true)) {
            return ['do_not_allow'];
        }
        return $caps;
    }

    /**
     * Método para registrar los filtros de gestión de usuarios.
     * Se llama desde Main.php en el hook 'init'.
     */
    public function restrict_user_management()
    {
        add_filter('editable_roles', [$this, 'filter_editable_roles']);
        add_filter('map_meta_cap', [$this, 'filter_map_meta_cap'], 10, 3);

        // Filtro para asegurar visibilidad de todos los productos
        add_action('pre_get_posts', [$this, 'ensure_all_products_visible'], 20);

        // Filtro para personalizar acciones de fila en productos
        add_filter('post_row_actions', [$this, 'customize_product_row_actions'], 20, 2);
    }

    /**
     * Asegura que los roles personalizados vean todos los productos (sin filtro por autor)
     */
    public function ensure_all_products_visible($query)
    {
        // Solo en la pantalla de productos de WooCommerce
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id === 'edit-product' && $this->is_custom_role()) {
            // Elimina cualquier filtro por autor
            $query->set('author', '');
        }
    }

    /**
     * Personaliza las acciones de fila en productos para roles personalizados
     * Solo deja: Ver, Editar, Papelera, Duplicar (sin Edición rápida)
     */
    public function customize_product_row_actions($actions, $post)
    {
        // Override total para productos y roles personalizados
        if ($this->is_custom_role() && $post->post_type === 'product') {
            $custom_actions = [];

            // Ver
            $view_link = get_permalink($post->ID);
            if ($view_link) {
                $custom_actions['view'] = '<a href="' . esc_url($view_link) . '" target="_blank">' . esc_html__('Ver', 'woocommerce') . '</a>';
            }

            // Editar (enlace manual)
            $edit_link = admin_url('post.php?post=' . $post->ID . '&action=edit');
            $custom_actions['edit'] = '<a href="' . esc_url($edit_link) . '">' . esc_html__('Editar', 'woocommerce') . '</a>';

            // Papelera (enlace manual)
            $trash_link = admin_url('post.php?post=' . $post->ID . '&action=trash');
            $custom_actions['trash'] = '<a href="' . esc_url($trash_link) . '" class="submitdelete" onclick="return confirm(\'¿Seguro que deseas enviar este producto a la papelera?\');">' . esc_html__('Papelera') . '</a>';

            // Duplicar (enlace manual)
            $duplicate_link = admin_url('admin.php?action=duplicate_post_save_as_new_post&post=' . $post->ID . '&post_type=product');
            $custom_actions['duplicate'] = '<a href="' . esc_url($duplicate_link) . '" title="Duplicar" rel="nofollow">' . esc_html__('Duplicar') . '</a>';

            return $custom_actions;
        }
        // error_log('Default actions: ' . print_r($actions, true));
        return $actions;
    }

    /**
     * Callback para limpiar el menú admin según el rol.
     * Se registra desde Main.php con el hook 'admin_menu'.
     */
    public function cleanup_admin_menu()
    {
        // estas opciones son para todos los roles personalizados
        if ($this->is_custom_role()) {
            // Menús principales a ocultar
            remove_menu_page('index.php'); // Escritorio
            remove_menu_page('edit.php'); // Entradas
            remove_menu_page('edit.php?post_type=page'); // Páginas
            remove_menu_page('edit-comments.php'); // Comentarios
            remove_menu_page('themes.php'); // Apariencia/Plantillas
            remove_menu_page('tools.php');
            remove_menu_page('options-general.php');
            remove_menu_page('plugins.php');


            remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag'); //Página admin de etiquetas
            remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category'); //Página admin de categorías

            //sacar Productos sub revision
            remove_submenu_page('edit.php?post_type=product', 'product-reviews');
            //sacar Productos sub atributos
            remove_submenu_page('edit.php?post_type=product', 'product_attributes');

            //Plantillas - edit.php?post_type=elementor_library&tabs_group=library
            remove_menu_page('edit.php?post_type=elementor_library');
        }

        // estas opciones solo para content_manager solamente
        // site_manager puede añadir usuarios
        if ($this->is_content_manager() && !$this->is_site_manager()) {
            // Usuarios: solo dejar Perfil
            remove_menu_page('users.php'); // Usuarios>Usuarios
        }
    }

    /**
     * Callback para redirección de login según rol.
     * Se registra desde Main.php con el hook 'login_redirect'.
     */
    public function fix_login_redirect($redirect_to, $requested, $user)
    {
        // Si el usuario es 'content_manager' o 'site_manager', siempre redirigir al dashboard PRODUCTOS
        if ($this->is_custom_role()) {
            return admin_url('edit.php?post_type=product');
        }

        // Por defecto, dejar la redirección original
        return $redirect_to;
    }

    /**
     * Limpia la barra de administración para roles personalizados.
     * Se registra desde Main.php con el hook 'wp_before_admin_bar_render'.
     */
    public function cleanup_admin_bar()
    {
        //limpiar barra admin para is_site_manager o is_content_manager
        if ($this->is_custom_role()) {
            global $wp_admin_bar;

            $this->remove_wp_logo($wp_admin_bar);
            $this->remove_search($wp_admin_bar);
            $this->remove_customize($wp_admin_bar);
            $this->remove_elementor($wp_admin_bar);
            $this->remove_comments($wp_admin_bar);
            $this->customize_site_menu($wp_admin_bar);
            $this->customize_new_content_menu($wp_admin_bar);
        }
    }

    /******************* INTERNAL PRIVATE METHODS */
    /**
     * Quita el botón de comentarios de la barra superior.
     */
    private function remove_comments($wp_admin_bar)
    {
        $wp_admin_bar->remove_node('comments');
    }

    /**
     * Quita el logo de WordPress de la barra superior.
     */
    private function remove_wp_logo($wp_admin_bar)
    {
        $wp_admin_bar->remove_node('wp-logo');
    }

    /**
     * Quita la lupa/búsqueda de la barra superior.
     */
    private function remove_search($wp_admin_bar)
    {
        $wp_admin_bar->remove_node('search');
    }

    /**
     * Quita el menú de "Personalizar" de la barra superior.
     */
    private function remove_customize($wp_admin_bar)
    {
        $wp_admin_bar->remove_node('customize');
    }

    /**
     * Quita el menú de "Editar con Elementor" si existe.
     */
    private function remove_elementor($wp_admin_bar)
    {
        $wp_admin_bar->remove_node('elementor_edit_page');
    }

    /**
     * Personaliza el top menú del sitio para mostrar solo lo necesario.
     */
    private function customize_site_menu($wp_admin_bar)
    {
        $allowed = ['view-site', 'view-store'];
        $site_menu = $wp_admin_bar->get_node('site-name');
        if ($site_menu) {
            // Si estamos en el frontend, aseguramos el enlace principal y agregamos los submenús
            if (!is_admin()) {
                // Cambiar el enlace principal al home del sitio
                $wp_admin_bar->add_node([
                    'id' => 'site-name',
                    'title' => get_bloginfo('name'),
                    'href' => home_url('/'),
                    'meta' => [
                        'class' => 'ab-item',
                    ],
                ]);
                // Agregar "Visitar sitio"
                $wp_admin_bar->add_node([
                    'id' => 'view-site',
                    'parent' => 'site-name',
                    'title' => 'Visitar sitio',
                    'href' => home_url('/'),
                ]);
                // Agregar "Visitar la tienda" si existe la página
                $tienda_url = home_url('/catalogo/');
                $wp_admin_bar->add_node([
                    'id' => 'view-store',
                    'parent' => 'site-name',
                    'title' => 'Visitar la tienda',
                    'href' => $tienda_url,
                ]);
            }

            // Eliminar otros submenús que no sean los permitidos
            foreach ($wp_admin_bar->get_nodes() as $node) {
                if ($node->parent === 'site-name' && !in_array($node->id, $allowed, true)) {
                    $wp_admin_bar->remove_node($node->id);
                }
            }

            // Agregar submenús personalizados (siempre, para ambos contextos)
            $wp_admin_bar->add_node([
                'id' => 'solicitudes-menu',
                'parent' => 'site-name',
                'title' => 'Solicitudes',
                'href' => admin_url('edit.php?post_type=solicitud_producto'),
            ]);

            $wp_admin_bar->add_node([
                'id' => 'productos-menu',
                'parent' => 'site-name',
                'title' => 'Productos',
                'href' => admin_url('edit.php?post_type=product'),
            ]);

            $wp_admin_bar->add_node([
                'id' => 'multimedia-menu',
                'parent' => 'site-name',
                'title' => 'Multimedia',
                'href' => admin_url('upload.php'),
            ]);
        }
    }

    /**
     * Personaliza el menú "+Agregar" para mostrar solo lo necesario.
     */
    private function customize_new_content_menu($wp_admin_bar)
    {
        // Solo dejar "Producto" y agregar "Multimedia"
        $allowed = ['new-product', 'new-media-custom'];
        $all_new = $wp_admin_bar->get_node('new-content');
        if ($all_new) {
            $children = $wp_admin_bar->get_nodes();
            foreach ($children as $node) {
                if ($node->parent === 'new-content' && !in_array($node->id, $allowed, true)) {
                    $wp_admin_bar->remove_node($node->id);
                }
            }

            // Eliminar "Entrada" si existe
            $wp_admin_bar->remove_node('new-post');

            // Agregar "Multimedia" si no existe
            if (!$wp_admin_bar->get_node('new-media-custom')) {
                $wp_admin_bar->add_node([
                    'id' => 'new-media-custom',
                    'parent' => 'new-content',
                    'title' => 'Multimedia',
                    'href' => admin_url('media-new.php'),
                ]);
            }
        }
    }
}///end class
