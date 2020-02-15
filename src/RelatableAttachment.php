<?php

namespace Armincms\Fields;

use Laravel\Nova\Rules\Relatable; 
use Laravel\Nova\Nova;

class RelatableAttachment extends Relatable 
{
    /**
     * Authorize that the user is allowed to relate this resource.
     *
     * @param  string  $resource
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function authorize($resource, $model)
    {   
        $parentModel = $this->request->resourceId 
                            ? $this->request->findModelOrFail() : $this->request->model();

        $parentResource = Nova::resourceForModel($parentModel);

        return (new $parentResource($parentModel))->authorizedToAttachAny(
            $this->request, $model
        ) || (new $parentResource($parentModel))->authorizedToAttach(
            $this->request, $model
        );
    }
}
