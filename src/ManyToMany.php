<?php

namespace Armincms\Fields;

use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Field; 
use Laravel\Nova\Fields\DetachesPivotModels; 
use Laravel\Nova\Fields\ResourceRelationshipGuesser; 
use Laravel\Nova\Fields\FormatsRelatableDisplayValues; 
use Laravel\Nova\TrashedStatus;
use Illuminate\Support\Str;
use Laravel\Nova\Nova;

abstract class ManyToMany extends Field
{  
    use DetachesPivotModels, FormatsRelatableDisplayValues;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'armincms-belongs-to-many'; 

    /**
     * The class name of the related resource.
     *
     * @var string
     */
    public $resourceClass;

    /**
     * The URI key of the related resource.
     *
     * @var string
     */
    public $resourceName;

    /**
     * The name of the Eloquent "belongs to many" relationship.
     *
     * @var string
     */
    public $manyToManyRelationship;

    /**
     * The callback that should be used to resolve the pivot fields.
     *
     * @var callable
     */
    public $fieldsCallback; 

    /**
     * The column that should be displayed for the field.
     *
     * @var \Closure
     */
    public $display; 

    /**
     * The label of resource selection.
     *
     * @var string
     */
    public $placeholder;

    /**
     * Determine if attach a resource multiple.
     *
     * @var string
     */
    public $duplicate = false; 


    /**
     * Determine if attach the pivot columns.
     *
     * @var string
     */
    public $pivots = false; 

    /**
     * Indicates whether the field should display the "With Trashed" option.
     *
     * @var bool
     */
    public $displaysWithTrashed = true;


    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|null  $attribute
     * @param  string|null  $resource
     * @return void
     */
    public function __construct($name, $attribute = null, $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->manyToManyRelationship = $this->attribute;
        $this->deleteCallback = $this->detachmentCallback();

        $this->fieldsCallback = function () {
            return [];
        };        

        $this->fillCallback = function ($pivots) {
            return (array) $pivots;
        };        
    } 

    /**
     * Determine if the field should be displayed for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorize(Request $request)
    {
        return call_user_func(
            [$this->resourceClass, 'authorizedToViewAny'], $request
        ) && parent::authorize($request);
    }

    /**
     * Resolve the field's value.
     *
     * @param  mixed  $resource
     * @param  string|null  $attribute
     * @return void
     */
    public function resolve($resource, $attribute = null)
    { 
        $value = null;

        if ($resource->relationLoaded($this->manyToManyRelationship)) {
            $value = $resource->getRelation($this->manyToManyRelationship);
        }

        if (! $value) {
            $value = $resource->{$this->manyToManyRelationship}()
                              ->withoutGlobalScopes()
                              ->getResults();
        } 

        $this->value = collect($value)->map(function($resource) {
            $display = $this->formatAttachableResource(
                app(NovaRequest::class), new $this->resourceClass($resource)
            );

            return array_merge(['pivotId' => $resource->pivot->id], $display);
        });
    }   

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return mixed
     */
    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if ($request->exists($requestAttribute)) { 
            $value = collect($request[$requestAttribute])->map([$this, 'normalize']);   

            $model::saved(function($model) use ($value, $request, $requestAttribute) {
                $authorized = $this->removeNonAuthorizedAttachments($request, $value, $model);
                
                $relationship = $model->{$this->manyToManyRelationship}()->withPivot('id');

                $attaching = $authorized->reject->attached; 

                $detaching = $this->mergeDetachments($model, $authorized); 

                $relationship->wherePivotIn('id', $detaching->pluck('pivotId')->all())
                            ->detach($detaching->pluck('id')->all());

                if(! $this->duplicate) { 
                    $attaching = $this->removeDuplicateAttachments($model, $attaching)
                                        ->keyBy('id')
                                        ->map([$this, 'fetchPivotValues']) 
                                        ->all();

                    $relationship->syncWithoutDetaching($attaching);
                } else {
                    $attaching->each(function($attachment) use ($relationship) { 
                        $relationship->attach(
                            $attachment['id'], $this->fetchPivotValues($attachment)
                        );
                    }); 
                }  
            }); 
        } 
    }

    /**
     * Convert field data to correct format.
     * 
     * @param  array $attachment 
     * @return array             
     */
    public function normalize($attachment)
    {  
        $attachment['attached'] = filter_var($attachment['attached'], FILTER_VALIDATE_BOOLEAN);
        $attachment['id'] = (int) $attachment['id'];

        return $attachment;
    }

    /**
     * Remove non authorized attachments.
     * 
     * @param  \Laravel\Nova\Http\Requests\NovaRequest $request     
     * @param  array $attachments 
     * @param  integer $model       
     * @return array 
     */
    protected function removeNonAuthorizedAttachments(NovaRequest $request, $attachments, $model)
    { 
        return collect($attachments)->filter(function($attachment) use ($request, $model) {  
            return $this->authorizedToAttach($request, $attachment['id']); 
        });
    }

    /**
     * Detect if user can attach related resource
     * @param  NovaRequest $request    
     * @param  array $attachment 
     * @return boolean                  
     */
    protected function authorizedToAttach(NovaRequest $request, $attachment)
    { 
        $parentModel = $request->resourceId 
                            ? $request->findModelOrFail() : $request->model();

        $parentResource = Nova::resourceForModel($parentModel);


        return (new $parentResource($parentModel))->authorizedToAttachAny(
            $request, $attachment
        ) || (new $parentResource($parentModel))->authorizedToAttach(
            $request, $attachment
        );
    }

    /**
     * Append database detachemnts into detachments.
     * 
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  array $authorized
     * @return array            
     */
    protected function mergeDetachments($model, $authorized)
    {
        $pivotKeys = $authorized->filter->attached->pluck('pivotId')->all();

        $shouldDetach = $model->{$this->manyToManyRelationship}()
                                ->withPivot('id')
                                ->wherePivotNotIn('id', $pivotKeys)->get()
                                ->map(function($related) {
                                    return [
                                        'id' => $related->id,
                                        'pivotId' => $related->pivot->id,
                                    ];
                                });

        return $authorized->reject->attached->merge($shouldDetach);          
    }

    /**
     * Remove related that before is attached
     * 
     * @param  \Illuminate\Database\Eloquent\Model $model     
     * @param  array $attaching 
     * @return array            
     */
    public function removeDuplicateAttachments($model, $attaching)
    {
        $attachments = $model->{$this->manyToManyRelationship}()->get()->pluck('id');

        return $attaching->reject(function($attachment) use ($attachments) {
            return $attachments->contains($attachment['id']);
        });        
    }

    /**
     * Apply the fillCalback into attachment pivots and fetch them.
     * 
     * @param  array $attachment 
     * @return array             
     */
    public function fetchPivotValues($attachment)
    { 
        return (array) call_user_func(
            $this->fillCallback, $attachment['pivots'] ?? [], $attachment['id']
        ); 
    } 

    /**
     * Build an attachable query for the field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  bool  $withTrashed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildAttachableQuery(NovaRequest $request, $withTrashed = false)
    {
        $model = forward_static_call([$resourceClass = $this->resourceClass, 'newModel']);

        $query = $resourceClass::buildIndexQuery(
                    $request, $model->newQuery(), $request->search, [], [], 
                    TrashedStatus::fromBoolean($withTrashed)
                 );

        return $query->tap(function ($query) use ($request, $model) {
            forward_static_call($this->attachableQueryCallable($request, $model), $request, $query);
        });
    }

    /**
     * Get the attachable query method name.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    protected function attachableQueryCallable(NovaRequest $request, $model)
    {
        return ($method = $this->attachableQueryMethod($request, $model))
                    ? [$request->resource(), $method]
                    : [$this->resourceClass, 'relatableQuery'];
    }

    /**
     * Get the attachable query method name.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    protected function attachableQueryMethod(NovaRequest $request, $model)
    {
        $method = 'relatable'.Str::plural(class_basename($model));

        if (method_exists($request->resource(), $method)) {
            return $method;
        }
    }

    /**
     * Format the given attachable resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  mixed  $resource
     * @return array
     */
    public function formatAttachableResource(NovaRequest $request, $resource, $attached = false)
    {
        return array_filter([
            'avatar'    => $resource->resolveAvatarUrl($request),
            'text'      => $this->formatDisplayValue($resource),
            'id'        => $resource->getKey(),
            'attached'  => $attached,
        ]);
    }

    /**
     * Specify the callback to be executed to retrieve the pivot fields.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function fields(callable $callback)
    {
        $this->fieldsCallback = $callback;

        return $this;
    } 

    /**
     * Set the label of the resource selection.
     *
     * @return string
     */
    public function placeholder($placeholder)
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Set the duplicate attachment status.
     *
     * @return string
     */
    public function duplicate(bool $duplicate = true)
    {
        $this->duplicate = $duplicate;

        return $this;
    }

    /**
     * Set the pivots attachment status.
     *
     * @return string
     */
    public function pivots(bool $pivots = true)
    {
        $this->pivots = $pivots;

        return $this;
    }

    /**
     * hides the "With Trashed" option.
     *
     * @return $this
     */
    public function withoutTrashed()
    {
        $this->displaysWithTrashed = false;

        return $this;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge([
            'belongsToManyRelationship' => $this->manyToManyRelationship, 
            'resourceName'  => $this->resourceName, 
            'placeholder'   => $this->placeholder,
            'duplicate'     => $this->duplicate, 
            'pivots'        => $this->pivots, 
            'withTrashed'   => $this->displaysWithTrashed, 
        ], parent::jsonSerialize());
    }
}
