<?php


namespace eluhr\jedison\widgets;

use eluhr\jedison\assets\DeepMergeAsset;
use eluhr\jedison\assets\JedisonAsset;
use stdClass;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

/**
 * @property-write \yii\web\JsExpression|string $theme
 * @property-write stdClass|string|array $schema
 */
class JedisonEditor extends InputWidget
{

    // Available Themes
    public const THEME_DEFAULT = 'default';
    public const THEME_THEME_BOOTSTRAP3 = 'bootstrap3';
    public const THEME_THEME_BOOTSTRAP4 = 'bootstrap4';
    public const THEME_THEME_BOOTSTRAP5 = 'bootstrap5';

    /**
     * @var array the HTML attributes for the textarea container tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $containerOptions = [];
    /**
     * Options to be passed to the jedison.
     * List of valid options can be found here:
     * https://github.com/germanbisurgi/jedison?tab=readme-ov-file#instance-options
     */
    public array $pluginOptions = [];
    /**
     * Events to be passed to the jedison
     */
    public array $pluginEvents = [];
    /**
     * Shows model errors
     */
    public bool $showModelErrors = false;
    /**
     * Use Jedison-translated error messages when showing model errors, if available.
     */
    public bool $mapTranslations = true;
    /**
     * Filter error messages by constraints to hide unnecessary messages.
     */
    public array $filterConstraints = [];
    /**
     * A json that contains the schema to build the form. Values can be given as array, string or stdClass
     * Does not to be set if pluginOptions property has schema set.
     */
    protected array $_schema;
    /**
     * Defined theme. Either set is as a string from the const THEME_ or as a JsExpression
     */
    protected JsExpression $_theme;

    /**
     * Custom validation errors either to add custom or set them if widget is initialized without a model
     *
     * @example [['constraint' => '$filters', 'messages' => ['Your message'], 'path' => '#/test']]
     */
    protected array $_validationErrors = [];

    /**
     * Disable the editor and set it in a readonly mode
     */
    public bool $disabled = false;

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException if some config is not as expected
     */
    public function init()
    {
        parent::init();

        // Remove error section of the active field template because we handle errors another way
        if (!empty($this->field)) {
            $this->field->error(false);
        }

        // If schema is set in plugin options use it from there
        if (isset($this->pluginOptions['schema'])) {
            $this->setSchema($this->pluginOptions['schema']);
            unset($this->pluginOptions['schema']);
        }

        // If theme is set in plugin options use it from there
        if (isset($this->pluginOptions['theme'])) {
            $this->setTheme($this->pluginOptions['theme']);
            unset($this->pluginOptions['theme']);
        }

        $this->ensurePluginOptions();

        if (!isset($this->_schema)) {
            throw new InvalidConfigException("Property 'schema' must be specified.");
        }

        $this->options['id'] = $this->id;

        // Always set a unique id for the container
        if (!isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = $this->options['id'] . '-container';
        }
    }

    /**
     * Convert schema to json array if given as string or stdClass
     */
    public function setSchema(array|stdClass|string $schema): void
    {
        if ($schema instanceof stdClass) {
            $schema = Json::encode($schema);
            // Now that the value is a string, it is converted to an array in the next condition.
        }

        if (is_string($schema)) {
            $schema = Json::decode($schema);
        }

        $this->_schema = $schema;
    }

    /**
     * Allow theme either from a string or a JsExpression
     */
    public function setTheme(JsExpression|string $theme): void
    {
        if (is_string($theme)) {
            $theme = self::themeMap()[$theme] ?? new JsExpression('new Jedison.Theme()');
        }
        $this->_theme = $theme;
    }

    /**
     * List of valid themed indexed as the theme value from const and value must be a JsExpression
     */
    protected static function themeMap(): array
    {
        return [
            self::THEME_DEFAULT => new JsExpression('new Jedison.Theme()'),
            self::THEME_THEME_BOOTSTRAP3 => new JsExpression('new Jedison.ThemeBootstrap3()'),
            self::THEME_THEME_BOOTSTRAP4 => new JsExpression('new Jedison.ThemeBootstrap4()'),
            self::THEME_THEME_BOOTSTRAP5 => new JsExpression('new JeJedisondi.ThemeBootstrap5()'),
        ];
    }

    /**
     * Reset all plugin options that should not be overwritten
     */
    protected function ensurePluginOptions(): void
    {
        unset($this->pluginOptions['container']);

        if (isset($this->pluginOptions['hiddenInputAttributes']['name']) && $this->hasModel() && empty($this->name)) {
            $this->name = $this->pluginOptions['hiddenInputAttributes']['name'];
        }

        if (isset($this->pluginOptions['hiddenInputAttributes']['id']) && $this->hasModel() && empty($this->options['id'])) {
            $this->options['id'] = $this->pluginOptions['hiddenInputAttributes']['id'];
        }

        unset($this->pluginOptions['hiddenInputAttributes']['name']);
        unset($this->pluginOptions['hiddenInputAttributes']['id']);

        if (!isset($this->_theme)) {
            $this->setTheme(self::THEME_DEFAULT);
        }
        // Set theme
        $this->pluginOptions['theme'] = $this->_theme;


        // Set default ref parser if not set
        if (!isset($this->pluginOptions['refParser'])) {
            $this->pluginOptions['refParser'] = new JsExpression('new Jedison.RefParser()');
        }

        // Set default value
        if ($this->hasModel()) {
            $data = $this->model->{$this->attribute};
        } else {
            $data = $this->value;
        }

        // Check if value is set before checking json
        if (!is_null($data)) {
            // Check if value is valid json. json_decode throws an error which we can "catch" with the json_last_error function
            json_decode($data);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->pluginOptions['data'] = new JsExpression($data);
            } else {
                Yii::warning('Data is not a valid JSON.');
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function run(): string
    {
        $this->registerAssets();
        return Html::tag('div', '', $this->containerOptions);
    }

    /**
     * Register all needed asset bundles and scripts
     */
    protected function registerAssets(): void
    {
        DeepMergeAsset::register($this->view);
        JedisonAsset::register($this->view);

        // Setup variables for later use
        $containerId = $this->containerOptions['id'];

        if (!empty($this->options['id'])) {
            $inputId = $this->options['id'];
        } else {
            $inputId = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->options['id'];
        }

        if (!empty($this->name)) {
            $inputName = $this->name;
        } else {
            $inputName = $this->hasModel() ? Html::getInputName($this->model, $this->attribute) : $this->name;
        }
        $id = Inflector::slug($containerId, '');

        // Escape json for config if needed
        $schema = Json::htmlEncode($this->_schema);
        $pluginOptions = Json::htmlEncode($this->pluginOptions);

        $refParser = $this->pluginOptions['refParser'] ?? null;

        $errorMessages = Json::htmlEncode($this->getValidationErrors());

        $disabled = Json::htmlEncode($this->disabled);

        $showModelErrors = Json::htmlEncode($this->showModelErrors);

        $mapTranslations = Json::htmlEncode($this->mapTranslations);

        $filterConstraints = Json::htmlEncode($this->filterConstraints);

        $pluginEvents = Json::htmlEncode($this->pluginEvents);

        // Init editor
        $this->view->registerJs(<<<JS
const initEditor$id = async () => {
  const schema = $schema || {}
  const refParser = $refParser || null

  if (refParser) {
    await refParser.dereference(schema)
  }

  const defaultOptions = {
    container: document.getElementById('$containerId'),
    schema: schema,
    hiddenInputAttributes: {
      'name': '$inputName',
      'id': '$inputId'
    }
  }

  const customOptions = $pluginOptions
  const customEvents = $pluginEvents
  let editorOptions = deepMerge(defaultOptions, customOptions)

  if (refParser) {
    editorOptions.refParser = refParser
  }

  const editor = new Jedison.Create(editorOptions)
  if (editor) {

    if ($disabled) {
      editor.disable()
    }

    Object.entries(customEvents).forEach(([event, value]) => {
      editor.on(event, value)
    })

    const editorErrors = editor.getErrors()

    let modelErrors = $errorMessages

    // Use Jedison error translations messages if any for custom errors
    if ($mapTranslations) {
      modelErrors = modelErrors.map(custom => {
        const matchingEditor = editorErrors.find(editor =>
          editor.path === custom.path
        );

        return {
          ...custom,
          messages: matchingEditor ? matchingEditor.messages : custom.messages
        };
      });
    }

    let errors = $showModelErrors && modelErrors.length > 0 ? modelErrors : editorErrors

    // filter out errors by constraint name
    if ($filterConstraints && $filterConstraints.length > 0) {
      errors = errors.filter((error) => {
        return !$filterConstraints.includes(error.constraint)
      })
    }

    // Always show initial model errors if there are any backend errors
    if ($showModelErrors && modelErrors.length > 0) {
      editor.showValidationErrors(errors)
    }

    window['$inputId'] = editor
  }
}

initEditor$id()
JS
        );
    }

    /**
     * Reformat validations errors and merge model errors if needed.
     */
    protected function getValidationErrors(): array
    {
        // Get errors from model xor validation errors set by user
        $errors = $this->hasModel() ? ArrayHelper::merge($this->model->getErrors($this->attribute), $this->_validationErrors) : $this->_validationErrors;

        $validationErrors = [];
        foreach ($errors as $error) {
            try {
                $validationErrors[] = is_array($error) ? $error : Json::decode($error);
            } catch (InvalidArgumentException $e) {
                // This exception occurs if error is not in valid json format
                Yii::error($e->getMessage());
                continue;
            }
        }

        // Use filter to remove potential malformed validation errors
        return array_filter($validationErrors);
    }

    public function setValidationErrors(array $validationErrors): void
    {
        $this->_validationErrors = $validationErrors;
    }
}
