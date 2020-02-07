import {
  findInCatalog,
  useItemFromCatalog,
  disconnectFromCatalogItem
} from '../../../api/shop'
import InputTag from 'vue-input-tag';

export default {
  components: {
        InputTag,
  },
  props: ['initialKeywords', 'submitUrl', 'csrfToken', 'postCatalogId', 'postId'],
  data() {
    return {
      catalogId: this.postCatalogId,
      searchByModel: 'Keywords',
      keywords: [],
      searchValue: '',
      isbn: '',
      catalogItems: [],
      searchInProgress: false,
    }
  },
  computed: {},
  mounted() {
    this.searchValue = this.initialKeywords.join(' ');
    this.searchBy('keywords', true);
  },
  methods: {
    searchBy: async function(by, isInitialSearch) {
      this.searchInProgress = true;

      // don't search for empty data
      if (!this.searchValue) {
        this.searchInProgress = false;
        if (!isInitialSearch) {
          alert('Please specifiy text or ISBN/MPN/UPC to search for.');
        }
        return false;
      }

      // search items and order them
      this.catalogItems = this.orderCatalogItems(
        (await findInCatalog(
          this.searchByModel.toLowerCase(),
          this.searchValue
        )).body
      );
      this.searchInProgress = false;
    },
    /**
     * we want to find item from list we got 
     * and put the one that match with catalogId 
     * to the top.
     */
    orderCatalogItems(items) {
      if (!items || !this.catalogId) {
        return items;
      }
      const matchedItemIndex = items.findIndex((item) => {
        return item.product_id == this.catalogId;
      })
      if (!matchedItemIndex) {
        return items;
      }
      // put the found item on top of the catalog list
      const matchedItem = items[matchedItemIndex];
      matchedItem.matchToCurrentPost = true; // just to highlight it in view
      items[matchedItemIndex] = items[0];
      items[0] = matchedItem;

      return items;
    },
    disconnect: async function() {
      try {
        await disconnectFromCatalogItem(this.postId);
      } catch (e) {
        return;
      }
      if (this.catalogItems[0] && this.catalogItems[0].matchToCurrentPost) {
        this.catalogItems[0].matchToCurrentPost = false;
      }
      this.catalogId = null;
    },
    goToItemOnEbay(e, item) {
      if ($(e.target).is(':button')) {
        return;
      }
      const win = window.open(item.details_url, '_blank');
      win.focus();
    },
    getSubmitUrlForItem(item) {
      return (this.submitUrl + '?product_id=' + item.product_id);
    }
  }
}
