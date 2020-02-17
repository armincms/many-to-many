<?php

namespace Armincms\Fields\Http\Controllers;
 
use Illuminate\Database\Eloquent\Relations\Pivot; 
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Contracts\ListableField;
use Laravel\Nova\Contracts\Resolvable;
use Laravel\Nova\ResourceToolElement;
use Armincms\Fields\ManyToMany; 
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Nova;

trait ResolvesFields  
{    
    public function fields(NovaRequest $request, $resource)
    {  
        $fields = $this->resolvePivotFields(
            $request, $resource, $request->relatedResource
        );

        if($request->isUpdateOrUpdateAttachedRequest()) {
            return $this->removeNonUpdateFields($request, $fields, $resource);
        }

        if($request->isCreateOrAttachRequest()) {
            return $this->removeNonCreationFields($request, $fields);
        }

        return $this->removeNonDetailFields($request, $fields, $resource)
                    ->each(function($field) use ($request, $resource) {
                        $relatedClass = Nova::resourceForKey($request->relatedResource);

                        $relatedResource = new $relatedClass(
                            $this->pivotModel($request, $resource, $request->relatedResource)
                        );

                        $field->resolveForDisplay($relatedResource); 
                    });
    } 

    /**
     * Resolve the pivot fields for the requested resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Resource  $resource
     * @param  string  $relatedResource
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    public function resolvePivotFields(NovaRequest $request, $resource, $relatedResource)
    { 
        $pivotModel = $this->pivotModel($request, $resource, $relatedResource);

        $fields = $this->pivotFieldsFor($request, $resource, $relatedResource);

        $resolveCallback = function ($field) use ($pivotModel) {
            if ($field instanceof Resolvable) {  
                $field->resolve($pivotModel);
            }
        };

        return FieldCollection::make($this->filter($fields->each($resolveCallback)->filter->authorize($request)->values()->all()))->values();
    }

    /**
     * Resolve the pivot model for the requested resource and requested related id.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Resource  $resource
     * @param  string  $relatedResource
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function pivotModel(NovaRequest $request, $resource, $relatedResource)
    { 
        $relatedField = $this->relatedFieldFor($request, $resource, $relatedResource);

        $accessor = $this->pivotAccessorFor($request, $resource, $relatedField);

        $query = $resource->{$relatedField->manyToManyRelationship}(); 

        if($related = $query->wherePivot('id', $request->pivotId)->first()) {
            return $related->{$accessor};
        } 

        return new Pivot;
    }

    /**
     * Get the pivot fields for the resource and relation.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $resource
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    public function pivotFieldsFor($request, $resource)
    {
        $field = $this->relatedFieldFor($request, $resource, $request->relatedResource); 

        if($field && isset($field->fieldsCallback)) {
            return FieldCollection::make(array_values(
                $this->filter(call_user_func($field->fieldsCallback, $request, $resource))
            ))->each(function ($field) {
                $field->pivot = true;
            }); 
        }
 
        return new FieldCollection;
    } 

    /**
     * Get the pivot fields for the resource and relation.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $relatedResource
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    public function relatedFieldFor(NovaRequest $request, $resource, $relatedResource)
    {
        return $resource->availableFields($request)
                        ->whereInstanceOf(ManyToMany::class)
                        ->where('resourceName', $relatedResource)
                        ->first();
    }  

    /**
     * Get the name of the pivot accessor for the requested relationship.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Resource $resource
     * @param  \Laravel\Nova\Field $field
     * @return string
     */
    public function pivotAccessorFor(NovaRequest $request, $resource, $field)
    { 
        return $resource->{$field->manyToManyRelationship}()->getPivotAccessor();
    }

    /**
     * Remove non-update fields from the given collection.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Fields\FieldCollection  $fields
     * @param  \Laravel\Nova\Resource  $resource
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    protected function removeNonUpdateFields(NovaRequest $request, FieldCollection $fields, $resource)
    {
        return $fields->reject(function($field) use ($request, $resource) {
            return $this->isNonEditableField($request, $field) || 
                    ! $field->isShownOnUpdate($request, $resource);
        });
    }  

    /**
     * Remove non-creation fields from the given collection.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Fields\FieldCollection  $fields
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    protected function removeNonCreationFields(NovaRequest $request, FieldCollection $fields)
    {
        return $fields->reject(function($field) use ($request) {
            return $this->isNonEditableField($request, $field) || 
                    ! $field->isShownOnCreation($request);
        });
    } 

    /**
     * Remove non-creation fields from the given collection.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Fields\FieldCollection  $fields
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    protected function removeNonDetailFields(NovaRequest $request, FieldCollection $fields, $resource)
    {
        return $fields->reject(function($field) use ($request, $resource) {
            return ! $field->isShownOnDetail($request, $resource);
        });
    } 
    
    /**
     * Detect if the field cannot be edit.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Fields\Field  $field
     * @return boolean
     */
    protected function isNonEditableField(NovaRequest $request, $field)
    {
        $resource = $request->newResource();

        return  $field instanceof ListableField ||
                $field instanceof ResourceToolElement ||
                $field->attribute === 'ComputedField' ||
                ($field instanceof ID && $field->attribute === $resource->getKeyName());
    }
}
