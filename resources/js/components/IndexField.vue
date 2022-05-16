<template>
  <span>
    <template v-if="field.value && field.value.length">
      <Link
        v-for="(resource, index) in field.value"
        :component="'detail'"
        :key="index"
        :href="`/nova/resources/${field.resourceName}/${resource.id}`"
        :data="{
          resourceName: field.resourceName,
          resourceId: resource.id,
        }"
        class="no-underline font-bold dim text-primary"
      >
        {{ resource.text }} {{ field.value.length - index - 1 ? " , " : "" }}
      </Link>

      
    </template>
    <!-- <template v-else><p #value >&mdash;</p></template> -->
  </span>
</template>

<script>
import { Link } from "@inertiajs/inertia-vue3";
export default {
  props: ["resourceName", "field"],

  components: {Link}
};
</script>
