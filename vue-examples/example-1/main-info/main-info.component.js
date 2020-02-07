import {
    getTitleFromtitleTemplate,
    getGeneratedTitle,
} from '../../../api/post';

const TITLE_MAX_LENGTH = 80;

export default {
    name: 'main-info-section',
    components: {},
    props: {
        category: {
            type: Object,
            required: true
        },
        metaOptions: {
            type: Array,
            required:true
        },
        post:{
            type:Object,
            required:true
        },
        rules:{
            type:Array,
            required:true
        },
        hasTitleTemplate: {
            required:true
        },
        isInhouseNotes:{
            type:Boolean,
            required:true
        },
        originalKeywords: {
            type: Array,
            required:true
        }
    },
    data() {
        return {
            title: this.post.title,
            content: this.post.content,
            titleCharactersLeft: TITLE_MAX_LENGTH,
            isSubmitDisabled: false,
            optionsForTitle: [],
            showWarningAboutTitle: false,
        }
    },
    watch: {
        title: {
            immediate: true,
            handler: function(val) {
                if (!val) {
                    return;
                }

                this.titleCharactersLeft = TITLE_MAX_LENGTH - val.replace(new RegExp('[{}]', 'g'), '').length;

                if (this.titleCharactersLeft < 0) {
                    this.isSubmitDisabled = true;
                } else {
                    this.isSubmitDisabled = false;
                }
            }
        }
    },
    async mounted() {
        this.insertBottomThanks();
        this.calculateOptions();

        const response = await getGeneratedTitle(this.post.id);
        if (this.post.title && response.body.title) {
            if (this.title != response.body.title) {
                this.showWarningAboutTitle = true;
            }

        }

    },
    methods: {
        async generateTitle() {
            const response = await getGeneratedTitle(this.post.id);
            if (response.body.title) {
                this.title = response.body.title;
                this.showWarningAboutTitle = false;
                return;
            }
            alert('The Title is not generated. Probably because there are no weights defined for the Motif or this Post has no Motif assigned.');
        },
        getTileButtonClass(tile) {
            const isPresent = this.title.toLowerCase().indexOf(tile.toLowerCase()) !== -1;
            return isPresent ? 'btn-primary' : 'btn-default';
        },
        addKeywordToTitle(keyword) {
            this.title += ' ' + keyword;
        },
        calculateOptions() {
            let options = this.metaOptions.concat(this.post.keywords.split(','));
            for(let i=0; i<options.length; i++){
                if(options[i].value){
                  console.log(options[i].value)
                   options[i].value = options[i].value.replace(/ *\([^)]*\) */, '');
                   if(options[i].value.match(/n\/a/i)){
                       options.splice(i, 1);
                   }
                }
            }
            this.optionsForTitle = options;
        },
        isTitleAlreadyInserted() {
            return this.content.match(new RegExp(this.title));
        },
        insertBottomThanks() {
            var text = 'Sold as pictured. Thanks for looking!';

            this.content = this.content.replace(new RegExp(text), '');
            this.content = this.content + "\n" + text;
        },
        insertTitle() {
            if (this.title.length == 0) {
                alert('There is no title to insert. Please update tilte first.');
                return;
            }

            if (this.isTitleAlreadyInserted()) {
                if (confirm('The title is already there. Are you sure you want to duplicate it?')) {
                    this.content = this.title + "\n" + this.content;
                }

                return;
            }

            this.content = this.title + "\n" + this.content;
            this.insertBottomThanks();
        },
        insertMetaData() {
            if (this.metaOptions.length == 0) {
                alert('You have no meta options inserted yet.');
                return;
            }

            let strToAdd = '';
            this.metaOptions.forEach(function(option) {
                strToAdd += option.meta_type_by_type_id.name + ': ' + option.value + "\n";
            });

            this.content = this.content + strToAdd;
            this.insertBottomThanks();
        },
        insertSellerNote() {
            if (!this.post.user_defined_condition || this.post.user_defined_condition.description.length == 0) {
                alert('There is no notes from seller. Nothing to add to the description.');
                return;
            }

            this.content = this.content + "\n" + this.post.user_defined_condition.description + "\n";
            this.insertBottomThanks();
        },
        addValueToTitle(value) {
            const pos = this.getCaretPosition(this.$refs.title_input).end;

            var titleParts = [
                this.title.substr(0,pos),
                this.title.substr(pos),
            ];

            if (titleParts[1][0] !== ' ') {
                titleParts[1] = ' ' + titleParts[1];
            }
            // just capitalize each word's first letter
            titleParts[0] += ' ' + (value.split(' ').map((word) => word.charAt(0).toUpperCase().trim() + word.slice(1).trim()).join(' '));
            this.title = titleParts[0] + titleParts[1];
            this.title = this.title.replace(/\s\s+/g, ' ').trim();
        },
        async createTitleFromTitleTemplate() {
            const response = await getTitleFromtitleTemplate(this.post.id, this.category.id);
            this.title = response.body.title;
        },
        getCaretPosition (ctrl) {
            // IE < 9 Support
            if (document.selection) {
                ctrl.focus();
                var range = document.selection.createRange();
                var rangelen = range.text.length;
                range.moveStart ('character', -ctrl.value.length);
                var start = range.text.length - rangelen;
                return {'start': start, 'end': start + rangelen };
            }
            // IE >=9 and other browsers
            else if (ctrl.selectionStart || ctrl.selectionStart == '0') {
                return {'start': ctrl.selectionStart, 'end': ctrl.selectionEnd };
            } else {
                return {'start': 0, 'end': 0};
            }
        }
    }
}
