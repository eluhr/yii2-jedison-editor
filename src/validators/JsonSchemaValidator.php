<?php

namespace eluhr\jedison\validators;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Exceptions\InvalidKeywordException;
use Opis\JsonSchema\Filter;
use Opis\JsonSchema\Resolvers\FilterResolver;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator as OpisJsonSchemaValidator;
use stdClass;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\validators\Validator;

/**
 * @property-write stdClass|string|array $schema
 * @property string|null $message Is just used as a fallback if no error is raised by the json schema but the schema is
 *     not valid e.g. in a case of an exception
 */
class JsonSchemaValidator extends Validator
{
    /**
     * This validator is just for server side validation and does not support client side validation
     *
     * @inheritdoc
     */
    public $enableClientValidation = false;

    private stdClass $_schema;

    /**
     * List of filters for the given json schema. Array must be list of objects with instance of
     * Opis\JsonSchema\Filter. Key must be filter name in schema
     *
     * @example ['custom' = new MyCustomFilter()]
     *
     */
    public array $filters = [];

    protected ValidationResult $validationResult;

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if (empty($this->_schema)) {
            throw new InvalidConfigException("The 'schema' property must be set.");
        }

        if (empty($this->message)) {
            $this->message = Yii::t('validator', '{attribute} does not match the necessary constraints.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $validationResult = $this->getValidationResult($model->$attribute);

        if (!$validationResult->isValid()) {
            $validationErrors = $validationResult->error();
            if (is_null($validationErrors)) {
                $this->addError($model, $attribute, $this->message);
            }

            foreach ($this->getFormattedErrorMessages($validationErrors) as $errorMessage) {
                $this->addError($model, $attribute, json_encode($errorMessage) ?: Yii::t('poc', 'Undefined error.'));
            }
        }
    }

    /**
     * @throws \yii\base\ErrorException
     */
    public function getValidationResult(string $value): ValidationResult
    {
        // With 999 we ensure, that all potentials errors are shown at once
        $validator = new OpisJsonSchemaValidator(null, 999);

        $filterResolver = $validator->parser()->getFilterResolver();

        // Check if filter resolver is even configured.
        if (is_null($filterResolver) && !empty($this->filters)) {
            Yii::warning('Cannot use filters because filter resolver is not set.');
        }

        // Apply filters
        if ($filterResolver instanceof FilterResolver) {
            foreach ($this->filters as $index => $filter) {
                if ($filter instanceof Filter) {
                    $filterResolver->registerMultipleTypes($index, $filter);
                }
            }
        }

        try {
            return $validator->validate(Json::decode($value, false), $this->_schema);
        } catch (InvalidKeywordException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e->getMessage());
        }
    }

    public function getFormattedErrorMessages(ValidationError $validationErrors): array
    {
        $errorFormatter = new ErrorFormatter();
        return $errorFormatter->formatFlat($validationErrors, function (ValidationError $validationError) use ($errorFormatter) {
            return [
                'constraint' => $validationError->keyword(),
                'path' => '#' . $errorFormatter->formatErrorKey($validationError),
                'messages' => array_values($errorFormatter->format($validationError, false))
            ];
        });
    }

    public function setSchema(array|string|stdClass $schema): void
    {
        // If schema is an array, encode so it turns into a string so it gets convert in the next step
        if (is_array($schema)) {
            $schema = Json::encode($schema);
        }

        if (is_string($schema)) {
            $schema = Json::decode($schema, false);
        }

        if (!$schema instanceof stdClass) {
            throw new InvalidArgumentException('Given schema cannot be convert to JSON object.');
        }

        $this->_schema = $schema;
    }
}
