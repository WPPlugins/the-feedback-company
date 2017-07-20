<?php

// security - stop if file isn't accessed via WP
if (!defined('ABSPATH'))
	exit;


// scripts & css stylesheets

function feedbackcompany_scripts_admin()
{
	if (!is_admin())
		return;
     
	// Add the color picker css file       
	wp_enqueue_style( 'wp-color-picker' ); 

	// Include our custom jQuery file with WordPress Color Picker dependency
	wp_enqueue_script( 'custom-script-handle', plugins_url( 'custom-script.js', __FILE__ ), array( 'wp-color-picker' ), false, true ); 
}
add_action('admin_enqueue_scripts', 'feedbackcompany_scripts_admin');


// instellingen in admin dashboard

class feedbackcompany_admin_settings
{
	private $options;

	// init
	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_plugin_page'));
		add_action('admin_init', array($this, 'page_init'));
	}

	// pagina toevoegen aan menu
	public function add_plugin_page()
	{
		// This page will be under "Settings"
		add_options_page(
			'The Feedback Company',
			'The Feedback Company',
			'manage_options',
			'feedbackcompany-admin',
			array($this, 'create_admin_page')
		);
	}

	public function option_set_default($key, $value)
	{
		if (!isset($this->options[$key]) || !$this->options[$key])
			$this->options[$key] = $value;
	}

	// pagina opbouwen
	public function create_admin_page()
	{
		echo '<script>';
		echo '(function( $ ) { $(function() { $(\'.color-picker\').wpColorPicker(); }); })( jQuery );';
		echo '</script>';
		echo '<div class="wrap">';
		echo '<h2>The Feedback Company</h2>';

		echo '<p>De Feedback Company review widgets kunnen toegevoegd worden via \'Weergave\' -> \'Widgets\' in het menu links, of via shortcodes:';
		echo '<br>[feedbackcompany_summary] - geeft verkort de gemiddelde score van klantbeoordelingen weer';
		echo '<br>[feedbackcompany_score] - geeft een widget met de gemiddelde score van klantbeoordelingen weer';
		echo '<br>[feedbackcompany_reviews] - geeft een slider widget weer met de meest recente klantbeoordelingen';
		echo '<br>[feedbackcompany_testimonial id=#] - geeft review widget met specifiek ID weer (vervang de # door het ID)';
		echo '</p>';

		if (!$this->options['oauth_client_id'] || !$this->options['oauth_client_secret'])
		{
			echo '<div class="updated notice"><p>';
			echo 'Voor deze plugin is een account bij The Feedback Company vereist.  Ga naar <a target="_blank" href="http://www.feedbackcompany.com/">www.feedbackcompany.com</a> als u nog geen account heeft en vul hieronder de OAuth gegevens in die u van The Feedback Company ontvangt.';
			echo '</p></div>';
		}
		elseif (!feedbackcompany_api_wp()->ext->get_cache('access_token'))
		{
			echo '<div class="error notice"><p>';
			echo 'Het is niet gelukt om met onderstaande OAuth gegevens in te loggen bij The Feedback Company.  Controleer de gegevens en neem eventueel contact met ons op.';
			echo '</p></div>';
		}

		echo '<form method="post" action="options.php">';

		// This prints out all hidden setting fields
		settings_fields('feedbackcompany_option_group');
		do_settings_sections('feedbackcompany-settings');
		submit_button();

		echo '</form>';
		echo '</div>';
	}

	// de instellingen definities
	public function page_init()
	{
		// get options from database and set defaults
		$this->options = get_option('feedbackcompany_options');
		$this->option_set_default('title_score', 'Klantbeoordelingen');
		$this->option_set_default('title_reviews', 'Recente beoordelingen');
		$this->option_set_default('title_testimonial', 'Beoordeling van');
		$this->option_set_default('color_header_text', '#eeeeee');
		$this->option_set_default('color_header_background', '#222222');
		$this->option_set_default('color_text', '#222222');
		$this->option_set_default('color_background', '#eeeeee');
		$this->option_set_default('star_color', '#ff8f3a');
		$this->option_set_default('star_color_negative', '#ffffff');
		$this->option_set_default('star_type', '9733');
		$this->option_set_default('star_size', '22');
		$this->option_set_default('star_scale', '5');
		$this->option_set_default('score_scale', '10');

		register_setting(
			'feedbackcompany_option_group',
			'feedbackcompany_options',
			array($this, 'sanitize')
		);

		$page = 'feedbackcompany-settings';

		$fields = array(
			'section_oauth' => array('section', 'OAuth '.__('Settings')),
			'oauth_client_id' => array('text', 'OAuth client ID'),
			'oauth_client_secret' => array('text', 'OAuth client secret'),
			'section_display' => array('section', 'Weergave'),
			'title_score' => array('text', 'Titel van de Score widget'),
			'title_reviews' => array('text', 'Titel van de Reviews widget'),
			'title_testimonial' => array('text', 'Titel van de Testimonial widget'),
			'color_header_text' => array('color', 'Kleur van de koptekst'),
			'color_header_background' => array('color', 'Kleur van de koptekst achtergrond'),
			'color_text' => array('color', 'Kleur van de tekst'),
			'color_background' => array('color', 'Kleur van de achtergrond'),
			'star_color' => array('color', 'Kleur van de sterren'),
			'star_color_negative' => array('color', 'Kleur van de sterren (negatief)'),
			'star_type' => array('startype', 'Ster type'),
			'star_size' => array('starsize', 'Ster grootte in pixels'),
			'star_scale' => array('scale', 'Aantal sterren (5 of 10)'),
			'score_scale' => array('scale', 'Score schaal (5 of 10)'),
			'section_richsnippet' => array('section', 'Rich snippet instellingen'),
			'richsnippet_schema' => array('schema', 'Schema'),
		);

		if (isset($this->options['richsnippet_schema']) && $this->options['richsnippet_schema'])
		{
			$current = $this->options['richsnippet_schema'];
			$properties = feedbackcompany_schemaorg()->getProperties($current);

			foreach ($properties as $property)
			{
				if (in_array('URL', $property['type']))
					$fields['richsnippet_'.$property['label']] = array('text', $property['label'].' (URL) <a href="https://schema.org/'.$property['label'].'" target="_blank">?</a>');
				elseif (in_array('Text', $property['type']))
					$fields['richsnippet_'.$property['label']] = array('text', $property['label'].' (tekst) <a href="https://schema.org/'.$property['label'].'" target="_blank">?</a>');
			}
		}

		$setting_section_id = null;

		foreach ($fields as $name => $field)
		{
			if ($field[0] == 'section')
			{
				add_settings_section(
					$name,
					$field[1],
					array($this, 'section_output'),
					$page
				);
				$setting_section_id = $name;
			}
			else
			{
				add_settings_field(
					$name,
					$field[1],
					array($this, 'field_output_'.$field[0]),
					$page,
					$setting_section_id,
					array($name)
				);
			}
		}
	}

	public function sanitize($input)
	{
		// cache leegmaken wanneer settings worden veranderd
		feedbackcompany_api_wp()->clear_cache();

		return $input;
	}

	// section info text
	public function section_output($section)
	{
		if ($section['id'] == 'section_oauth')
			echo 'Vul hier de instellingen voor OAuth in zoals u deze ontvangen heeft van The Feedback Company.';
		if ($section['id'] == 'section_display')
			echo 'Hier kunt u het uiterlijk van de widgets en shortcodes van de plugin aanpassen.';
		if ($section['id'] == 'section_richsnippet')
			echo 'Hieronder staan de belangrijkste rich snippet instellingen van schema.org.  Klik op het vraagteken bij een instelling om de schema.org documentatie te openen.';
	}

	// specifiek veld weergeven
	public function field_output_starsize($args)
	{
		printf(
			'<input type="number" min="10" max="50" id="'.$args[0].'" name="feedbackcompany_options['.$args[0].']" value="%s" />',
			isset($this->options[$args[0]]) ? esc_attr($this->options[$args[0]]) : ''
		);
	}
	public function field_output_scale($args)
	{
		printf(
			'<input type="number" min="5" max="10" step="5" id="'.$args[0].'" name="feedbackcompany_options['.$args[0].']" value="%s" />',
			isset($this->options[$args[0]]) ? esc_attr($this->options[$args[0]]) : ''
		);
	}
	public function field_output_text($args)
	{
		printf(
			'<input type="text" id="'.$args[0].'" name="feedbackcompany_options['.$args[0].']" size="70" value="%s" />',
			isset($this->options[$args[0]]) ? esc_attr($this->options[$args[0]]) : ''
		);
	}
	public function field_output_color($args)
	{
		$value = isset($this->options[$args[0]]) ? esc_attr($this->options[$args[0]]) : '';
		echo '<input type="text" id="'.$args[0].'" name="feedbackcompany_options['.$args[0].']" class="color-picker" value="'.$value.'" />';
	}
	public function field_output_startype($args)
	{
		$characters = array(9733, 9734, 10022, 10023, 10025, 10026, 10027, 10028, 10029, 10030, 10031, 10032, 10033, 10038, 10039, 10040 );
		$i = 0;
		foreach ($characters as $character)
		{
			echo '<input style="vertical-align: bottom;" type="radio" name="feedbackcompany_options['.$args[0].']" ';
			if (isset($this->options[$args[0]]) && $this->options[$args[0]] == $character)
				echo 'checked ';
			echo 'value="'.$character.'" />';
			echo '<span style="font-size: 16px; display: inline-block; width: 40px;">&#'.$character.';</span>';
			$i++;
			if ($i % 5 == 0)
				echo '<br>';
		}
	}
	public function field_output_schema($args)
	{
		$current = isset($this->options[$args[0]]) ? esc_attr($this->options[$args[0]]) : '';
		echo '<select name="feedbackcompany_options['.$args[0].']">';
		echo '<option value="">rich snippet uitgeschakeld';
		$tree = array_merge(
			feedbackcompany_schemaorg()->getTree('Organization'),
			feedbackcompany_schemaorg()->getTree('CreativeWork')
		);
		foreach ($tree as $schema)
		{
			$id = trim(substr($schema, strrpos($schema, "\n")));
			echo '<option value="'.$id.'"';
			if ($id == $current)
				echo ' selected';
			echo '>'.str_replace("\n", ' - ', $schema)."\n";
		}
		echo '</select>';
	}
}


// settings page alleen beschikbaar voor admins
if (is_admin())
{
	$feedbackcompany_admin_settings_page = new feedbackcompany_admin_settings();
}

