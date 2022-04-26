<?php

namespace Givebutter\LaravelCustomFields\Traits;

use Givebutter\LaravelCustomFields\Exceptions\FieldDoesNotBelongToModelException;
use Givebutter\LaravelCustomFields\Exceptions\WrongNumberOfFieldsForOrderingException;
use Givebutter\LaravelCustomFields\Models\CustomField;
use Givebutter\LaravelCustomFields\Validators\CustomFieldValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait HasCustomFields
{
    /**
     * Get the custom fields belonging to this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function customFields($group = null)
    {
        $rel = $this->morphMany(CustomField::class, 'model')->where('group', $group)->orderBy('order');
        return $rel;
    }

    public function allCustomFields()
    {
        $rel = $this->morphMany(CustomField::class, 'model')->orderBy('group')->orderBy('order');
        return $rel;
    }

    /**
     * Validate the given custom fields.
     *
     * @param $fields
     * @return CustomFieldValidator
     */
    public function validateCustomFields($fields)
    {
        $validationRules = $this->customFields()->whereNull('archived_at')->get()->mapWithKeys(function ($field) {
            return ['field_' . $field->id => $field->validationRules];
        })->toArray();

        $keyAdjustedFields = collect($fields)
            ->mapWithKeys(function ($field, $key) {
                return ["field_{$key}" => $field];
            })->toArray();

        return new CustomFieldValidator($keyAdjustedFields, $validationRules);
    }

    public function validateCustomField($field_id, $value)
    {
        $field = $this->customFields()->whereNull('archived_at')->where('id', $field_id)->first();
        $validationRules = [
            "input" => $field->validationRules,
        ];

        $keyAdjustedFields = [
            "input" => $value
        ];

        return Validator::make($keyAdjustedFields, $validationRules);
    }

    /**
     * Validate the given custom field request.
     *
     * @param Request $request
     * @return CustomFieldValidator
     */
    public function validateCustomFieldsRequest(Request $request)
    {
        return $this->validateCustomFields($request->get(config('custom-fields.form_name', 'custom_fields')));
    }

    /**
     * Handle a request to order the fields.
     *
     * @param $fields
     * @throws FieldDoesNotBelongToModelException
     * @throws WrongNumberOfFieldsForOrderingException
     */
    public function order($fields, $group = null)
    {
        $fields = collect($fields);

        if ($fields->count() !== $this->customFields($group)->count()) {
            throw new WrongNumberOfFieldsForOrderingException($fields->count(), $this->customFields($group)->count());
        }

        $fields->each(function ($id, $index) use ($group){
            $customField = $this->customFields($group)->find($id);

            if (!$customField) {
                throw new FieldDoesNotBelongToModelException($id, $this);
            }

            $customField->update(['order' => $index + 1]);
        });
    }
}
