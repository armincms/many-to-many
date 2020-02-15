<?php

namespace Armincms\Fields;
 
use Laravel\Nova\Fields\Select;  

class MorphToMany extends BelongsToMany
{    
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
        parent::__construct($name, $attribute, $resource); 

        $this->pivots();    
        $this->fields(function() {});   
    }

    /**
     * Specify the callback to be executed to retrieve the pivot fields.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function fields(callable $callback)
    {
        $this->fieldsCallback = function($request, $resource) use ($callback) { 
            $relation = $this->getMorphToMany($resource);

            return collect(call_user_func($callback, $request, $resource))->prepend(
                Select::make(__("Resource"), $relation->getMorphType($resource))
                    ->options([
                        $relation->getMorphClass() => $resource::label(),
                    ])
                    ->onlyOnForms()
                    ->required()
                    ->rules('required')
                    ->withMeta(['value' => $relation->getMorphClass()])
            )->all(); 
        };

        return $this;
    }  

    /**
     * Indicated the MorphToMany relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    protected function getMorphToMany($resource)
    { 
        $model = $resource::newModel();

        return $model->{$this->manyToManyRelationship}();  
    }  

    /**
     * Set the pivots attachment status.
     *
     * @return string
     */
    public function pivots(bool $pivots = true)
    {  
        $this->pivots = true;

        return $this;
    } 
}
