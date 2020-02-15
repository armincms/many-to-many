<template>
  <panel-item :field="field">
    <template slot="value" v-if="field.value && field.value.length">
      <span v-if="field.pivots"> 
      	<span  
      		class="no-underline font-bold dim text-primary cursor-pointer"
        	v-for="(resource, index) in field.value"
        	:key="resource.id"
        	@click="displayPivots(resource)"
        	:title="__(':resource Details', { resource: resource.text })"
        >
          {{ resource.text }} {{ field.value.length - index - 1 ? ' , ' : '' }}
        </span>
      </span> 
      <span v-else>
        <router-link 
          v-for="(resource, index) in field.value"
          :key="index"
          :to="{
            name: 'detail',
            params: {
              resourceName: field.resourceName,
              resourceId: resource.id,
            },
          }"
          class="no-underline font-bold dim text-primary"
          :title="__('View')"
        > 
          {{ resource.text }} {{ field.value.length - index - 1 ? ' , ' : ''  }}
        </router-link>
      </span>
       <modal v-if="field.pivots && display" role="dialog" @modal-close="handleClose">  
            <loading-view :loading="loading"> 
							<card :class="'w-action-fields'"> 
			          <form-heading-field 
			            :field="{
			              asHtml: true,
			              value: display.text  
			            }" 
			            class="mb-6 pt-6"
			          />
 
	    					<div class="mb-6 py-3 px-6">
	                <!-- Pivot Fields --> 
	                <component
	                	v-for="(field, index) in fields"
	                	:key="index"
	                  :is="'detail-' + field.component"
	                  :resource-name="resourceName"
	                  :field="field" 
	                  :via-resource="viaResource"
	                  :via-resource-id="viaResourceId"
	                  :via-relationship="viaRelationship"
	                /> 
	              </div>

	              <div class="px-6 py-3 flex">
	                <div class="flex items-center ml-auto">
			            	<button  
                    	class="btn btn-link dim cursor-pointer text-80 ml-auto mr-6"
			            		type="button" 
			            		@click.prevent="handleClose">{{ 
				            	__("Close") 
				            }}</button>
			            	<router-link 
							        :to="{
							          name: 'detail',
							          params: {
							            resourceName: field.resourceName,
							            resourceId: display.id,
							          },
							        }"
							        class="no-underline font-bold dim text-primary"
            					:title="__('View')"
							      > 
            				{{ __(':resource Details', { resource: display.text }) }}
							      </router-link>
							    </div>
							  </div>  
            	</card>
            </loading-view>
       </modal>
    </template>
    <p v-else slot="value">&mdash;</p>
  </panel-item>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

    data() {
    	return {
    		display: false,
    		fields: [],
    	}
    },

    methods: {
    	displayPivots(resource) {
    		this.display = resource;
    		this.loading = false;

    		this.getPivotFields()  
    	}, 

      /**
       * Get all of the available fields for an attachment.
       */
      async getPivotFields(resource) {   
        await Nova.request()
          .get(
            `/nova-api/armincms/${this.resourceName}/pivot-fields/${this.field.resourceName}`,
            {
              params: { 
                resourceId: this.resourceId,
                relatedId: this.display.id,   
                pivotId: this.display.pivotId,   
              },
            }
          ) 
          .then(({ data }) => { 
            this.fields = data

            _.each(this.fields, field => {
              field.fill = () => ''   
            })
          }) 
      }, 

	    /**
	     * Resolve the component name.
	     */
	    resolveComponentName(field) {
	      return field.prefixComponent
	        ? 'detail-' + field.component
	        : field.component
	    },

    	handleClose() {
    		this.display = null

        this.$emit('close')
    	},
    },

    computed: {
    },
}
</script>
