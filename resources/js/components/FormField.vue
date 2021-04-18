<template>
  <default-field :field="field" :errors="errors">
    <template slot="field">
      <vue-tags-input
        v-model="tag"
        :tags="attachedResources" 
        :autocomplete-min-length=0
        :add-only-from-autocomplete=true
        :add-from-paste=false
        :add-on-blur=false
        :allow-edit-tags=true
        :placeholder="placeholder"
        :is-duplicate="isDuplicate"
        :autocomplete-items="filteredResources"
        :avoid-adding-duplicates="! field.duplicate" 
        @input="performSearch"
        @before-adding-tag="addingTag"  
        @before-editing-tag="editingTag"  
        @tags-changed="tags => attachedResources = tags"
      >
        <div slot="tag-center" slot-scope="props"> 
          <span 
            @click="performEditTag(props)" 
            > 
            {{ props.tag.text }} 

            <svg 
              v-if="field.pivots"
              xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
              viewBox="0 0 347 347" width="15px"
              style="cursor: pointer;"  
            > 
              <polygon fill="#fff" 
                points="284.212,0 231.967,51.722 295.706,115.461 347.429,63.216"/>
              <polygon fill="#fff" 
                points="0,347.429 85.682,319.216 28.212,261.747"/>
            
              <rect fill="#fff" x="115.322" y="56.259" width="90.14" height="261.554"
                transform="matrix(-0.7071 -0.7071 0.7071 -0.7071 141.551 432.7058)" /> 
            </svg>
          </span>          
        </div>
      </vue-tags-input> 

      <modal v-if="processingResource" role="dialog" @modal-close="cancelProcessing">  
            <!-- @keydown="handleKeydown" -->
          <form
            autocomplete="off"
            class="bg-white rounded-lg shadow-lg overflow-hidden"
            :class="'w-action-fields'"
            @submit.prevent.stop="attachTheResource"
            :name="field.attribute"
            :id="field.attribute"
          >
          <form-heading-field 
            :field="{
              asHtml: true,
              value: processingResource.attached 
                ? __('Update :resource', { resource: processingResource.text })
                : __('Attach :resource', { resource: processingResource.text }) 
            }" 
            class="mb-6"
          />


          <p class="text-80 px-8 my-1"> 
            <loading-view :loading="loading"> 
              <card> 
                <!-- Pivot Fields -->
                <div v-for="field in fields">
                  <component
                    :is="'form-' + field.component"
                    :resource-name="resourceName"
                    :field="field"
                    :errors="validationErrors"
                    :via-resource="viaResource"
                    :via-resource-id="viaResourceId"
                    :via-relationship="viaRelationship"
                  />
                </div>
              </card>
              <div class="px-6 py-3 flex">
                <div class="flex items-center ml-auto">
                  <button
                    dusk="cancel-attach-button"
                    type="button"
                    @click.prevent="cancelProcessing"
                    class="btn btn-link dim cursor-pointer text-80 ml-auto mr-6"
                  >
                    {{ __("Cancel Attaching") }}
                  </button>

                  <progress-button
                    ref="attachButton"
                    dusk="confirm-attach-button" 
                    type="submit"
                    class="btn btn-default"
                    :class="'btn-primary'" 
                    :disabled="processing"
                    :processing="processing"
                    :form="field.attribute"
                  > 
                    {{ __("Attach Resource") }}
                  </progress-button>
                </div>
              </div>
            </loading-view>
          </p>

          </form> 
      </modal>
    </template>
  </default-field>
</template>


<style>
.vue-tags-input.ti-focus .ti-input {
  background-color: var(--white);
  outline: 0;
  -webkit-box-shadow: 0 0 0 3px var(--primary-50);
  box-shadow: 0 0 0 3px var(--primary-50);
}
.vue-tags-input {
  max-width: 100% !important;
}
.ti-input {
  border-color: var(--60) !important;
  border-radius: 0.5rem !important;
  padding: 2px !important;
}
.ti-autocomplete {
  overflow-y: scroll;
  max-height: 200px;
}
.ti-autocomplete .ti-item {
  padding: 5px;
}
.ti-tag-center svg:hover polygon, .ti-icon-close:hover {
  fill: var(--black);
  color: var(--black);
}
.ti-tag-center svg:hover rect, .ti-icon-close:hover {
  fill: var(--black);
  color: var(--black);
}
.ti-tags .ti-tag {
  border-radius: 8px !important;
  margin: 3px !important;
}
.ti-tags .ti-tag.ti-valid {
  background: var(--success) !important;
}
.ti-autocomplete .ti-item.ti-selected-item {
  background: var(--info) !important
}
</style>

<script>
import { FormField, HandlesValidationErrors, Errors } from 'laravel-nova'
 
import { VueTagsInput, createTag, createTags } from '@johmun/vue-tags-input';

export default {
    mixins: [FormField, HandlesValidationErrors],

    components: {
      VueTagsInput,
    },

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return { 
          tag: '',
          attachedResources: [], 
          pivots: [], 
          fields: [], 
          search: '', 
          loading: false, 
          validationErrors: new Errors(),
          processingResource: null,
          resourceProcessor: null,
          cancelCallback: () => this.resetCallbak(),
          attachCallback: () => this.resetCallbak(),
          resetCallbak: function() { 
            this.processingResource = null
            this.resourceProcessor = null
          },
          processing: false,
          availableResources: [],
          withTrashed: false,
          softDeletes: false, 
        }
    },

    created() { 
      if (! this.field.searchable) {
        this.getAvailableResources(); 
      }
      
      this.getAttachedResources(); 
    },

    methods: {
      /*
       * Set the initial, internal value for the field.
       */
      setInitialValue() {
          this.attachedResources = []/*this.field.value || ''*/
      },

      /**
       * Fill the given FormData object with the field's internal value.
       */
      fill(formData) {     
        if(this.fillResources.length == 0) {
          formData.append(this.field.attribute, this.fillResources)
        } else {
          this.appendToForm(this.fillResources, formData, this.field.attribute)  
        } 
      },

      performSearch(search) { 
        if (! this.field.searchable) return

        this.search = search; 

        setTimeout(()  => {
          if (this.search == search && this.search.length > 0) { 
            this.getAvailableResources()
          } 
        }, 500)
      },

      appendToForm(object, formData, prefix) {   
        for (var key in object) {
          if(key == 'pivotAccessor') { 
            this.mergeFormData(this.pivots[object[key]], formData, prefix + this.wrap('pivots')) 
          } else if("object" == typeof object[key]) { 
            this.appendToForm(object[key], formData, prefix + this.wrap(key)) 
          } else { 
            formData.append(prefix + this.wrap(key), object[key])
          } 
        }  
      },

      mergeFormData(formData, mergeForm, prefix) {
        for (var pair of formData.entries()) {  
          mergeForm.append(prefix + this.wrap(pair[0]), pair[1])
        }  
      },

      wrap(key) {
        return key.replace(/^([^\[]+)/, matches => "[" +matches+ "]");
      },

      /**
       * Update the field's internal value.
       */
      handleChange(value) {
        this.attachedResources = value 
      }, 

      addingTag(item) {  
        console.log('adding tag:', item.tag.text)
        this.processTheResource(item.tag, item.addTag)  
      },

      editingTag(item) {   
        console.log('editing tag:', item.tag.text) 
        this.processTheResource(item.tag, item.editTag)
      },

      // The duplicate function to recreate the default behaviour, would look like this:
      isDuplicate(tags, tag) {   
        return ! this.field.duplicate && tags.map(tag => tag.id).indexOf(tag.id) >= 0; 
      },

      performEditTag(props) {  
        if(this.field.pivots) {
          this.cancelCallback = () => { 
            props.performCancelEdit(props.index)

            this.attachCallback = this.cancelCallback = () => {}
          }  

          this.attachCallback = () => {  
            props.performSaveEdit(props.index) 

            this.attachCallback = this.cancelCallback = () => {}

            return props.index;
          }  

          props.performOpenEdit(props.index)    
        }
      },

      async attachTheResource() {  
        if(await this.validatePivotFields(this.processingResource)) { 
          await this.resourceProcessor();   
   
          console.log('attached the resource:', this.processingResource.text)   

          var index = await this.attachCallback() 

          await this.resetCallbak() 

          index = typeof index == 'number' ? index : this.attachedResources.length - 1 

          this.pivots[index] = this.attachmentFormData

          this.$set(this.attachedResources, index, _.tap(this.attachedResources[index], tag => {
            tag.attached = false
            tag.pivotAccessor = index
          })) 

          this.processingModal = false;  
        } 
      },

      async validatePivotFields(resource) {   
        if (this.fields.length > 0) {  

          try { await this.validateRequest(resource) } catch (error) {   
            if (error.response.status == 422) {
              this.validationErrors = new Errors(error.response.data.errors)
              Nova.error(this.__('There was a problem submitting the form.'))
            } 

            return false;
          } 
        }  

        return true   
      },

      validateRequest(resource) {
        return Nova.request().post(
            `/nova-api/armincms/${this.resourceName}/pivots-validate/${this.field.resourceName}`,
            this.attachmentFormData,
            {
              params: {
                field: this.field.attribute, 
                resourceId: this.resourceId,
                relatedId: this.processingResource.id, 
                editing: true,
                editMode: resource.attached ? 'update-attached' : 'attach' 
              },
            }
          )
      },

      async processTheResource(resource, processor) {
        this.validationErrors = new Errors();
          
        this.loading = true

        this.processingResource = resource;
        this.resourceProcessor  = processor;  

        this.field.pivots && await this.getPivotFields(resource) 

        this.processingModal = this.fields.length > 0 ? true : this.attachTheResource() 

        this.loading = false;

        console.log('processing resource:', this.processingResource.text)
      }, 

      triggerLoading() {
        this.loading = ! this.loading;
      },

      cancelProcessing() { 
        this.processingModal = false; 

        console.log('canceled attachment:', this.processingResource.text)

        this.cancelCallback();

        this.$emit('close')

        this.resetCallbak()
      },   

      /**
       * Get all of the available resources for the current search / trashed state.
       */
      getAvailableResources() { 
        Nova.request()
          .get(
            `/nova-api/armincms/${this.resourceName}/attachable/${this.field.resourceName}`,
            {
              params: {
                search: this.search,
                field: this.field.attribute,
                resourceId: this.resourceId,
                withTrashed: this.field.withTrashed,
              },
            }
          )
          .then(({ data }) => { this.availableResources = data })
      },

      /**
       * Get all of the available resources for the current search / trashed state.
       */
      getAttachedResources() { 
        Nova.request()
          .get(
            `/nova-api/armincms/${this.resourceName}/attached/${this.field.resourceName}`,
            {
              params: {
                field: this.field.attribute,
                resourceId: this.resourceId,
                withTrashed: this.field.withTrashed,
              },
            }
          )
          .then(({ data }) => { this.attachedResources = data })
      },

      createTag(resource) {
        return _.tap(createTag(resource.text, this.attachedResources), (tag) => {
            tag.attached = resource.attached
            tag.id = resource.id
        })
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
                field: this.field.attribute, 
                resourceId: this.resourceId,
                relatedId: this.processingResource.id, 
                pivotId: this.processingResource.pivotId, 
                editing: true,
                editMode: resource.attached ? 'update-attached' : 'attach' 
              },
            }
          ) 
          .then(({ data }) => { 
            this.fields = data

            _.each(this.fields, field => {
              field.fill = () => ''  

              var pivots = this.pivots[resource.pivotAccessor] 
                              ? this.pivots[resource.pivotAccessor] 
                              : new FormData

              if(pivots.has(field.attribute)) {
                field.value = pivots.getAll(field.attribute)
              } 
            })
          }) 
      }, 
    },

    computed: {  
      filteredResources() {    
        return this.availableResources.filter(item => { 
          return this.tag.length === 0 || item.text.match(this.tag);
        });
      },  

      /**
       * Get the form data for the resource attachment.
       */
      attachmentFormData() {
        return _.tap(new FormData(), formData => {
          _.each(this.fields, field => { 
            field.fill(formData)
          }) 
        })
      },

      /**
       * Return the placeholder text for the field.
       */
      placeholder() {
        return this.field.placeholder || this.__('Choose an option')
      },

      fillResources() {
        return this.attachedResources.map(resource => { 
          delete resource.text
          delete resource.tiClasses  

          return resource
        })
      }
    }
}
</script>