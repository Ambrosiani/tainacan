# Creating a custom Metadata type 

Metadata types are the objects that represent the types of metadata that can be used. Examples of Metadata Types are "Text", "Long text", "Date", "Relationship with another item", etc;

Each metadata type object have its own settings and web component that will be used to render the interface. 

Developers can create custom Metadata Types via plugins. This article shows how to do this.

A Metadata Type is composed of a simple PHP class and a Vue Web Component.


## PHP Class

### Registering your Metadata Type 

First of all, you have to register you Metadata type class. You do this by calling the `register_metadata_type` method of the `Metadata` Repository:

```PHP
add_action('init', 'my_custom_mt_registration');

function my_custom_mt_registration() {
	$Tainacan_Metadata = \Tainacan\Repositories\Metadata::get_instance();
	$Tainacan_Metadata->register_metadata_type('MyCustomMetadataTypeClass');
}

```

Now you have to create the class you just register, and use its constructor to set the basic features of you Metadata Type:

```PHP
class MyCustomMetadataTypeClass {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->set_name( __('My Custom Metadata Type', 'my-domain') );
		$this->set_description( __('My Custom Metadata Type', 'my-domain') );
		$this->set_primitive_type('string');
		$this->set_component('my-custom-component');
		
	}
	
}
```

These are the basic methods you have to call to set up you MT:

* **set_name(string $name)** - Sets the name of the Metadata type. This is the name the user will see in the front end and should be internationalized.
* **set_description(string $name)** - Sets the description of the Metadata type. This is the text the user will see in the front end and should be internationalized.
* **set_primitive(string $type)** - Inform what is the raw type of data that will be stored. This is used mainly by Filter Types to decide what kind of Filters will be available for each Metadata Type. Current used primitive types are: string, date, float, item, term and long_string (you can create new ones if you want, just note that not Filter Types will be available to them)
set_preview_template 

You can also set a HTML preview of your Metadata Type to help users visualize how it works. This will be displayed in the Metadata configuration screen, when the user click on the "?" icon of a Metadata Type.

* **set_preview_template(string $template)** - The HTML code that previwes the plugin. Use Buefy classes.


### Metadata type Options 

When you register a new Metadata Type, it will be automatically added as an option in the Metadata configuration screen. It will also have the default metadata configuration form, where you set whether the metadata is required or not, accept multiple values or not, and so on.

However, you metadata type may have specific options you want to give to the users. For example: In the Selectbox Metadata type, you have the ability to set what will be the options in your Selectbox.

In order to do this, you have to declare what are the options your metadata type has, and prepare another web component to be rendered in the metadata form:

```PHP
class MyCustomMetadataTypeClass {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->set_name( __('My Custom Metadata Type', 'my-domain') );
		$this->set_description( __('My Custom Metadata Type', 'my-domain') );
		$this->set_primitive_type('string');
		$this->set_component('my-custom-component');
		
		// custom options 
		$this->set_form_component('my-custom-form-component');
		$this->set_default_options([
            'my_option_1' => 'value 1',
            'my_option_2' => 'value 2'
        ]);
		
	}
	
}
```

Optionally you can implement `validate_options` method to validate the form before it gets saved:

```PHP
class MyCustomMetadataTypeClass {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->set_name( __('My Custom Metadata Type', 'my-domain') );
		$this->set_description( __('My Custom Metadata Type', 'my-domain') );
		$this->set_primitive_type('string');
		$this->set_component('my-custom-component');
		
		// custom options 
		$this->set_form_component('my-custom-form-component');
		$this->set_default_options([
            'my_option_1' => 'value 1',
            'my_option_2' => 'value 2'
        ]);
		
	}
	
	public function validate_options( Metadatum $metadatum ) {
		
		$option1 = $this->get_option('my_option_1');
		
		if ($option1) { // some condition
			return true; // validated!
		} else {
			return ['my_option_1' => __('This option is invald', 'my-domain')];
		}
		
	}
	
}
```

`validate_options` is expected to return `true` if valid, and an array where the keys are the option name and the values are the error messages.

### Additional Methods

There are few other methods you can implement that can change the items interact with metadata depending on the metadata type:

#### **validate( Item_Metadata_Entity $item_metadata)**

This method will override the validation of the Item Metadata Entity, which means every time Tainacan saves a value for a metadata of this type, it will call this method. For example, the Date Metadata Type override this method to make sure the date is in the correct format:

```PHP
public function validate( Item_Metadata_Entity $item_metadata) {
	$value = $item_metadata->get_value();
	$format = 'Y-m-d';

	if (is_array($value)) {
		foreach ($value as $date_value) {
			$d = \DateTime::createFromFormat($format, $date_value);
			if (!$d || $d->format($format) !== $date_value) {
				$this->add_error( 
					sprintf(
						__('Invalid date format. Expected format is YYYY-MM-DD, got %s.', 'tainacan'),
						$date_value
					)
				);
				return false;
			}
		}
		return True;
	}
	
	$d = \DateTime::createFromFormat($format, $value);
	
	if (!$d || $d->format($format) !== $value) {
		$this->add_error( 
			sprintf(
				__('Invalid date format. Expected format is YYYY-MM-DD, got %s.', 'tainacan'),
				$value
			)
		);
		return false;
	}
	return true;
	
}
```

Note that it checks whether the value is a string (single value) or an array (multiple values).


#### **get_value_as_html( Item_Metadata_Entity $item_metadata)**

This method will change the way a value is converted to HTML for metadata of this metadata type. For example, Taxonomy and Relationship Metadata Type use this to add links to the related term/item in the HTML output.

## Creating Vue Web Component