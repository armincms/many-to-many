Nova.booting((Vue, router, store) => {
  Vue.component('index-armincms-belongs-to-many', require('./components/IndexField'))
  Vue.component('detail-armincms-belongs-to-many', require('./components/DetailField'))
  Vue.component('form-armincms-belongs-to-many', require('./components/FormField')) 
})
