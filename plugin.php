<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor form Mailniaga action.
 *
 * Custom Elementor form action which adds new subscriber to Mailniaga v2 after form submission.
 *
 * @since 1.0.0
 */
class MailNiaga_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * Get action name.
	 *
	 * Retrieve Mailniaga action name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'mailniaga';
	}

	/**
	 * Get action label.
	 *
	 * Retrieve Mailniaga action label.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Mailniaga v2', 'elementor-forms-mailniaga-action' );
	}

	/**
	 * Register action controls.
	 *
	 * Add input fields to allow the user to customize the action settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	 
	
	public function register_settings_section( $widget ) {

		$widget->start_controls_section(
			'section_mailniaga',
			[
				'label' => esc_html__( 'Mailniaga v2', 'elementor-forms-mailniaga-action' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);
		
		//$widget->add_control(
			//'mailniaga_api_field',
			//[
				//'label' => esc_html__( 'Mailniaga API Token', 'elementor-forms-mailniaga-action' ),
				//'type' => \Elementor\Controls_Manager::TEXT,
				//'description' => __( 'To find it go to https://manage.mailniaga.com/account/api ', 'text-domain' ),
			//]
		//);

        $widget->add_control(
            'mailniaga_list_uid',
            [
                'label' => esc_html__( 'Mailniaga List UID', 'elementor-forms-mailniaga-action' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'description' => esc_html__( 'The list ID you want to subscribe a user to', 'elementor-forms-mailniaga-action' ),
                'options' => get_option( 'mailniaga_lists', [] ),
            ]
        );

		
		
		

		$widget->add_control(
			'mailniaga_email_field',
			[
				'label' => esc_html__( 'Email Field ID', 'elementor-forms-mailniaga-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$widget->add_control(
			'mailniaga_name_field',
			[
				'label' => esc_html__( 'Name Field ID', 'elementor-forms-mailniaga-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);
		
		$repeater = new \Elementor\Repeater();
		
		$repeater->add_control(
            'custom_field_name', [
                'label' => __( 'Custom field name', 'plugin-domain' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => __( 'Place the Name of the Mailniaga Custom Field', 'text-domain' ),
                'label_block' => true,
            ]
        );
        $repeater->add_control(
            'custom_field_id', [
                'label' => __( 'Custom field id', 'plugin-domain' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => __( 'Place the ID of the Elementor Field', 'text-domain' ),
                'label_block' => true,
            ]
        );

        $widget->add_control(
            'mailniaga_custom_fields',
            [
                'label' => __( 'Custom Fields', 'plugin-domain' ),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ custom_field_name }}}',
                'separator' => 'before'
            ]
        );

		$widget->end_controls_section();

	}


	/**
	 * Run action.
	 *
	 * Runs the Sendy action after form submission.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
    
    $settings = $record->get( 'form_settings' );

    //  Make sure that there is a Mail Niaga list ID.
    if ( empty( $settings['mailniaga_list_uid'] ) ) {
        return;
    }

    // Make sure that there is a Mail Niaga email field ID (required by Mail Niaga to subscribe users).
    if ( empty( $settings['mailniaga_email_field'] ) ) {
        return;
    }
    
    //  Make sure that there is a Sendy API Key
	//if ( empty( $settings['mailniaga_api_field'] ) ) {
		//	return;
//	}

    // Get submitted form data.
    $raw_fields = $record->get( 'fields' );

    // Normalize form data.
    $fields = [];
    foreach ( $raw_fields as $id => $field ) {
        $fields[ $id ] = $field['value'];
    }

    // Make sure the user entered an email (required by Mail Niaga to subscribe users).
    if ( empty( $fields[ $settings['mailniaga_email_field'] ] ) ) {
        return;
    }

    // Request data based on the param list at https://manage.mailniaga.com/api/v1/subscribers
    $mailniaga_data = [
        'EMAIL' => $fields[ $settings['mailniaga_email_field'] ],
        'list_uid' => $settings['mailniaga_list_uid'],
        'api_token' => get_option( 'mailniaga_api_key' ),
        'FIRST_NAME' => ucfirst($fields[ $settings['mailniaga_name_field'] ]),
        //'ipaddress' => \ElementorPro\Core\Utils::get_client_ip(),
        //'referrer' => isset( $_POST['referrer'] ) ? $_POST['referrer'] : '',
    ];
    
    $customFieldsLen = count($settings['mailniaga_custom_fields']);
        for($i = 0; $i < $customFieldsLen; $i++){
            $customFieldName = $settings['mailniaga_custom_fields'][$i]['custom_field_name'];
            $customFieldId = $settings['mailniaga_custom_fields'][$i]['custom_field_id'];
            $mailniaga_data[$customFieldName] = $fields[ $customFieldId ];
        }

    // Send the request.
    wp_remote_post(
        'https://manage.mailniaga.com/api/v1/subscribers',
        [
            'body' => $mailniaga_data,
        ]
    );
    
	    
	}


	/**
	 * On export.
	 *
	 * Clears Sendy form settings/fields when exporting.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $element
	 */
	public function on_export( $element ) {

		unset(
			//$element['sendy_url'],
			$element['mailniaga_list'],
			$element['mailniaga_email_field'],
			$element['mailniaga_name_field'],
			$element['mailniaga_custom_fields'],
			$element['custom_field_name'],
			$element['custom_field_id']
		);

		return $element;

	}

}
