<?php

namespace Armincms\Fields\Http\Controllers;
 
use Laravel\Nova\Http\Requests\NovaRequest; 

trait InteractsWithResourceRequest  
{  
    public function resource(NovaRequest $request, $resourceId = null)
    {   
        return $request->newResourceWith(
        	$request->findModelQuery($resourceId ?? $request->resourceId)->first() ?? $request->model()
        );
    }
}
