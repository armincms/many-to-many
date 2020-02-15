<template>  
	<span>
      <template v-if="field.value && field.value.length">
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
        >
          {{ resource.text }} {{ field.value.length - index - 1 ? ' , ' : ''  }}
        </router-link>  
      </template>
      <p v-else slote="value">&mdash;</p>
    </span> 
</template>

<script>
export default {
    props: ['resourceName', 'field'],  
}
</script>
