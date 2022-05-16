Nova.booting((Vue, router, store) => {
   Vue.component('index-armincms-belongs-to-many', require('./components/IndexField').default)
   Vue.component('detail-armincms-belongs-to-many', require('./components/DetailField').default)
   Vue.component('form-armincms-belongs-to-many', require('./components/FormField').default) 
})

