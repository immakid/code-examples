<template>
    <div>
        <h1 class="title">Posts sent</h1>

        <b-table
                :data="list"
        >

            <template slot-scope="props">

                <b-table-column label="ID">
                    {{props.row.post.id}}
                </b-table-column>

                <b-table-column field="date" label="Network" centered>

                    <span v-for="accountId in props.row.publish_params.social_accounts" class="">
                        <slotscope :social="socialAccount(accountId)">
                            <div slot-scope="{ social }">
                                <img :src="social.profile_image" alt="">
                                <i v-if="social.social_network_id == 1" class="fab fa-pinterest fa-1x m-right-5"></i>
                                <i v-if="social.social_network_id == 2" class="fab fa-instagram fa-1x m-right-5"></i>
                                <i v-if="social.social_network_id == 4" class="fab fa-twitter-square fa-1x m-right-5"></i>
                            </div>
                        </slotscope>
                    </span>

                </b-table-column>

                <b-table-column field="first_name" label="Media">
                    <img v-for="media in props.row.post.media_files" :src="getMediaUrl(media)" class="preview m-right-5" alt="">
                </b-table-column>

                <b-table-column field="last_name" label="Message">
                    {{props.row.post.text}}
                </b-table-column>

                <b-table-column field="last_name" label="Date">
                    {{props.row.scheduled_time}}
                </b-table-column>

            </template>
        </b-table>

        <b-button type="is-primary" v-show="showLoadNext" @click="loadNext">Load next</b-button>

    </div>

</template>

<script>
    import h from '../../../../app_vue/helpers';
    import mixins from '../../../../app_vue/components_mixins.js'
    import MediaFile from '../../../../app_vue/models/MediaFile'
    import SocialNetworkModel from '../../../../app_vue/models/SocialEngine/SocialNetworkModel'
    import PostModel from '../../../../app_vue/models/SocialEngine/PostModel'
    import UserSocialAccount from '../../../../app_vue/models/SocialEngine/UserSocialAccount'
    import PostScheduledTimeModel from '../../../../app_vue/models/SocialEngine/PostScheduledTimeModel'
    import BButton from "buefy/src/components/button/Button";
    //-=-=-=-=-=-=-=-=-=-=-=-=


    export default {

        name: "smma__sent_list",

        components: {BButton},
        mixins: [mixins],

        props: {

        },

        computed: {

        },

        data() {
            return {
                list: [],
                listPage: 0,

                showLoadNext: true,
            }
        },
        watch: {

        },
        async mounted()
        {

            this.loadListAsync();

        },
        methods: {

            loadNext()
            {
                this.loadListAsync();
            },

            socialAccount(id)
            {
                var data = _.find(this.$root.user_social_accounts, {id: id});

                return new UserSocialAccount(data);
            },

            getMediaUrl(media)
            {
                var modelMedia = new MediaFile(media);
                return modelMedia.getUploadsUrl();
            },

            async loadListAsync()
            {

                var respPosts = await h.repo.getModels(PostScheduledTimeModel, {
                    sort: 'scheduled_time',
                    order: 'desc',
                    page: this.listPage,
                    relations: ['post', 'post.mediaFiles', 'publishParams'],
                    where: [
                        ['process_status', '=', 'done'],
                    ],

                    with_is_next: 1
                });

                console.log(respPosts.list);

                this.list = _.concat(this.list, respPosts.list);
                this.showLoadNext = respPosts.is_next_exists;
                this.listPage = respPosts.page + 1;
            }
        }
    }
</script>

<style scoped lang="scss">

    .preview
    {
        max-height: 100px;
        max-width: 100px;
    }

</style>
