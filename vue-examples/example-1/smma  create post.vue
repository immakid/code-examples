<template>
    <section>

        <h1 class="title">Create post</h1>

        <b-field label="Social accounts">

            <div class="columns is-multiline">
                <div :class="{'is-active': account.active === true}" class="account column" v-for="(account, index) in socialAccounts"
                      >

                    <div @click="onClickAccount(index, account)">
                        <img :src=account.profile_image alt="">
                        <i v-if="account.active === true" class="fas fa-check-circle v-top"></i>
                        <p>
                            {{account.username}}
                        </p>
                    </div>

                    <b-dropdown v-if="account.social_network_id == 1"
                            v-model="selectedBoards[account.id]"
                            multiple
                            aria-role="list">
                        <button @click="onSelectBoard(account)"class="button is-primary" type="button" slot="trigger">
                            <span>Boards ({{ selectedBoards[account.id].length }})</span>
                            <b-icon icon="menu-down"></b-icon>
                        </button>

                        <b-dropdown-item v-for="board in pinterestAccountsBoards[account.id]"
                                         :value="board.id" aria-role="listitem">
                            <span>{{board.name}}</span>
                        </b-dropdown-item>

                    </b-dropdown>
                </div>
            </div>

        </b-field>

        <b-field class="" label="Post images/videos">
            <b-upload v-model="dropFiles"
                      multiple
                      class="m-right-10"
                      @input="onFilesChanged"
                      drag-drop>
                <div class="content has-text-centered">
                    <p>
                        <b-icon
                                icon="upload"
                                size="is-large">
                        </b-icon>
                    </p>
                    <p>Upload images</p>
                </div>
            </b-upload>

            <span v-for="(file, index) in dropFilesPreview"
                  :key="index"
                  class="is-vtop m-right-10" >

                <img :src="file.mediaFile.getUploadsUrl()" :alt="file.name" class="preview">

                <button class="delete is-small"
                        type="button"
                        @click="deleteDropFile(index)">
                </button>
            </span>
        </b-field>

        <b-field label="Message">
            <b-input v-model="text" maxlength="200" type="textarea"></b-input>
        </b-field>

        <b-field label="Pinterest link from post">
            <b-input v-model="link" ></b-input>
        </b-field>

        <b-field label="Scheduled time">
            <b-datepicker
                    :min-date="new Date()"
                    v-model="scheduleDate"
                    placeholder="Select scheduled time"
                    icon="calendar-today">
            </b-datepicker>
        </b-field>


        <div>

            <button class="button" type="button" @click="saveAsDraft">Save as draft</button>
            <button :disabled="disabledScheduleBtn" class="button is-primary" @click="publishNow">Publish now!</button>
            <button :disabled="disabledScheduleBtn" class="button is-primary" @click="schedule">Schedule</button>
        </div>

    </section>

</template>

<script>

    import h from '../../../../app_vue/helpers';
    import mixins from '../../../../app_vue/components_mixins.js'
    import MediaFile from '../../../../app_vue/models/MediaFile'
    //-=-=-=-=-=-=-=-=-=-=-=-=


    export default {
        mixins: [mixins],
        name: "smma__create_post",

        data() { return this.initData(); },

        async created() {

        },

        async mounted() {

            // var bundle = await h.repo.getModels(SocialNetworkModel, {
            //     models_list: 1
            // });
            //
            // var bundle = await h.repo.updateModels(SocialNetworkModel, [
            //     {id: 1, name: 'sdfds'}
            // ]);
            // debugger;


        },

        methods: {

            initData()
            {
                var pinterestAccountsBoards = {};
                var selectedBoards = {};

                var pinterestAccounts = _.filter(this.$root.user_social_accounts, {social_network_id: 1});
                _.map(pinterestAccounts, (item) => {
                    pinterestAccountsBoards[item.id] = [];
                    selectedBoards[item.id] = [];
                });
                pinterestAccountsBoards = _.extend(pinterestAccountsBoards, this.$root.user_pinterest_boards);

                return {

                    socialAccounts: _.cloneDeep(this.$root.user_social_accounts),
                    selectedBoards: selectedBoards,
                    pinterestAccountsBoards: pinterestAccountsBoards,

                    isScheduleBtnsActvie: false,

                    dropFiles: [],
                    dropFilesPreview:[],
                    mediaFiles: [],

                    disabledScheduleBtn: true,
                    scheduleDate: null,
                    link: '',
                    text: '',
                }
            },


            onClickAccount(index, account)
            {
                account.active = account.active === true ? false : true;
                this.$set(this.socialAccounts, index, account);

                this.checkActivateButtons();
            },

            checkActivateButtons()
            {
                var isDisabled = false;

                var activeAccounts = _.filter(this.socialAccounts, {active: true});
                if (!activeAccounts.length)
                {
                    isDisabled = true;
                }

                // if publish to visual networks, but no images attached
                var pinAccounts = _.filter(activeAccounts, {social_network_id: 1});
                if (pinAccounts.length)
                {
                    if (!this.dropFilesPreview.length)
                    {
                        isDisabled = true;
                    }
                }

                this.disabledScheduleBtn = isDisabled;
            },

            async onSelectBoard(account)
            {
                if (this.pinterestAccountsBoards[account.id].length == 0)
                {
                    var respBoards= await this.runTask('GetPinterestAccountsBoardsTask', {
                        accounts: [account.id]
                    });
            
                    if (respBoards.isOk())
                    {
                        this.pinterestAccountsBoards[account.id] = respBoards.get('boards')[0].list;
                    }
                }
            },

            saveAsDraft()
            {
                // this.apiSavePost('draft');

                this.resetForm();
            },

            publishNow()
            {
                this.apiSavePost('instantly');
            },

            schedule()
            {
                this.apiSavePost('scheduled');
            },

            async apiSavePost(creationType)
            {
                let formData = new FormData();
                // this.$buefy.toast.open('User confirmed');
                // var apiParams = {
                //     dropFiles: this.dropFiles
                // };
                // this.dropFiles.map(function (file, index) {
                //     formData.append('image'+index, file)
                // });

                var mediaIds = _.map(this.dropFilesPreview, 'mediaFile.id');
                var activeAccounts = _.filter(this.socialAccounts, {active: true});
                var accountsIds = _.map(activeAccounts, 'id');

                var activeBoards = _.map(accountsIds, (id) => {
                    return {
                        account_id: id,
                        board_ids: this.selectedBoards[id]
                    }
                });

                var publishParams = {
                    accounts: activeBoards
                };

                var respCreate = await this.runTask('CreatePostTask', {
                    text: this.text,
                    link: this.link,
                    media_files: mediaIds,
                    creation_type: creationType,
                    social_accounts: accountsIds,
                    scheduled_time: this.scheduleDate,
                    publish_params: JSON.stringify(publishParams)
                });

                if (respCreate.isOk())
                {
                    if (creationType == 'instantly')
                    {
                        var post = respCreate.get('post');

                        var respCreate = await this.runTask('PublishToSocialAccountsTask', {
                            post_id: post.id
                        });

                        if (respCreate.isOk())
                        {
                            this.toastOk('Post published');
                        }
                    }
                    else
                    {
                        this.toastOk('Post saved');
                    }
                }

                this.resetForm();
            },

            resetForm()
            {
                // this.$set(this.socialAccounts, index, account);
                Object.assign(this.$data, this.initData());
                this.$forceUpdate();
            },


            onFilesChanged(files)
            {
                var self = this;

                files.map(function (file, index) {

                    var previewItem = _.find(self.dropFilesPreview, {name: file.name});
                    if (previewItem) return;
                    //-=-=-=-=-=-=-=-=-=-=-=-=

                    let formData = new FormData();
                    formData.append('media', file);

                    h.api.post(h.route('api.media.upload'), formData)
                        .then((resp) => {

                            var res = apiRes(resp);
                            var mediaFile = res.get('res').mediaFile;

                            self.dropFilesPreview.push({
                                name: file.name,
                                mediaFile: new MediaFile(mediaFile)
                            });

                            self.checkActivateButtons();

                        });
                });
            },

            deleteDropFile(index) {
                this.dropFiles.splice(index, 1);
                this.dropFilesPreview.splice(index, 1);

                this.checkActivateButtons();
            }
        }
    }
</script>

<style scoped lang="scss">

    .preview
    {
        max-width: 100px;
        max-height: 100px;
    }

    .pictures
    {
        border: 1px solid #c2c8cc;
    }

    .account
    {
        opacity: 0.5;

        &.is-active
        {
            opacity: 1;
        }
    }

</style>
