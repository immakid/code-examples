import InputTag from 'vue-input-tag';
import Treeselect from '@riophae/vue-treeselect'
import {getShopCategories, getSuggestedCategories} from '../../../api/shop'

const  DEFAULT_TITLE = 'ebay';

export default {
    components: {
        InputTag,
        Treeselect,
    },
    props: ['keywords', 'originalKeywords', 'currentCategory', 'templates', 'isPostUsingMotif', 'showSubmitToCrowd'],
    data() {
        return {
            internalKeywords: this.keywords,
            template: this.getTemplate(),
            category: this.getCurrentCategory(),
            categories: null,
            suggestedCategories:false,
        }
    },
    computed: {},
    async mounted() {
        const response = await getShopCategories(this.template);
        this.categories = response.body.categories;


        this.getEbayCategories().then(() => {
            // set value for category_id input
            for (let category of this.categories) {
              if (this.currentCategory && (category.id == this.currentCategory.id) && (this.isPostUsingMotif == category.has_motif)) {
                $('input[name="category_id"]').val(category.id);
                $('input[name="using_motif"]').val(this.isPostUsingMotif ? 'true' : 'false');
                break;
              }
            }

            this.$nextTick(() => {
              this.attachSelectEventToSelecatableItems();
            })
        });
    },
    methods: {
        attachSelectEventToSelecatableItems() {
            $('.selectable').unbind('click');
            $('.selectable').on('click', e => {
                this.selectableItemClicked(e.currentTarget);
            });
        },
        setSelectedInputValue(inputName, value) {
            $('input[name="'+inputName+'"]').val(value);
        },
        selectKeyword (keyword) {
            this.internalKeywords.push(keyword);
        },
        selectableItemClicked(target) {
            if ($(target).hasClass('selected')) {
                $(target).removeClass('selected');
                this.setSelectedInputValue($(target).data('input-name'), '');
                this.setSelectedInputValue('using_motif', '');
                return;
            }

            $(target).siblings().removeClass('selected');
            $(target).addClass('selected');
            this.setSelectedInputValue($(target).data('input-name'), $(target).data('item-id'));
            this.setSelectedInputValue('using_motif', $(target).data('item-has-motif'));
        },
        async getEbayCategories() {
            const response = await getSuggestedCategories(this.template, this.internalKeywords.join(','));
            this.categories = response.body.categories;
            this.suggestedCategories = true;
            this.attachSelectEventToSelecatableItems();
        },
        async reset() {
            event.preventDefault();
            this.categories = null;
            this.category = null;
            const response = await getSuggestedCategories(this.template, this.internalKeywords.join(','));
            this.categories = response.body.categories;
            this.suggestedCategories = true;
        },
        getTemplate(){
            if(this.currentCategory){
                return this.currentCategory.shop ? this.currentCategory.shop.alias : DEFAULT_TITLE;
            }else{
                return DEFAULT_TITLE;
            }
        },
        getCurrentCategory(){
            return this.currentCategory ? this.currentCategory.id : null;
        },
        submitForm(action) {
          const form = document.getElementById('category-form');
          if (action) {
            $(form).append('<input type="hidden" name="action" value="' + action + '" />');
          }
          form.submit();
        },
        applyPostTemplate() {
            if (confirm('Are you sure you want to apply selected post template?')) {
                const form = document.getElementById('category-form');
                $(form).append('<input type="hidden" name="action" value="apply-post-template" />');
                form.submit();
            }
        },
        saveKeywords() {
            const form = document.getElementById('category-form');
            $(form).append('<input type="hidden" name="action" value="keywords" />');
            form.submit();
        },
        getTemplateImage(template) {
            const images = template.images;
            if (images.length > 0) {
                return images[0].url;
            }

            return null;
        }
    }
}
