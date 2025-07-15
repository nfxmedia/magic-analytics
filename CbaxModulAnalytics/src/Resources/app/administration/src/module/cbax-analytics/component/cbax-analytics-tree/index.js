import template from './cbax-analytics-tree.html.twig';
import './cbax-analytics-tree.scss';

const { Mixin, Component } = Shopware;
const { Criteria } = Shopware.Data;
const USER_CONFIG_KEY = 'cbax.analytics.favorites';

Component.register('cbax-analytics-tree', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('user-settings')
    ],

    props: {
		showTree: {
            type: Boolean,
            required: false,
            default: true
        },
        activeStatisticName: {
		    type: String,
            required: false
        }
    },

    data() {
        return {
            isLoading: false,
            statistics: [],
            treeItems: [],
            favoriteItems: [],
            statisticId: '',
            hasPickwareErpPro: false,
            coolbaxLink: 'https://store.shopware.com/de/search?manufacturer=3018d1831ce70ddcd3621deee9f5bc07&search=coolbax&isManufacturerPage=true&properties=00b8a0c9aacf4e8b4e085afe3819df45|ca1b9eb6b5f340b3b53694d0858e727f|cea1dbd38c8fc53eafa1a66a421897c8|67d8b30767b4fe71e53f1555703b26ce|87a78e4ead2a4528e6f81adea4caf2e4'
        };
    },

    computed: {
        disableContextMenu() {
            return false;
        },

        groupRepository() {
            return this.repositoryFactory.create('cbax_analytics_groups_config');
        },

        pluginRepository() {
            return this.repositoryFactory.create('plugin');
        },

        statisticsRepository() {
            return this.repositoryFactory.create('cbax_analytics_config');
        },

        currentUser() {
            return Shopware.State.get('session')?.currentUser;
        }
    },

    created() {
        this.getList();
    },

    watch: {
        activeStatisticName() {
            this.unsetStatistics();
        }
    },

    methods: {

        async getList(loadUserFav = true) {
            this.isLoading = true;

            this.hasPickwareErpPro = await this.testForPickware();

            let userSettings;
            if (loadUserFav) {
                try {
                    userSettings = await this.getUserSettings(USER_CONFIG_KEY, this.currentUser?.id);
                } catch (e) {

                }

                if (userSettings?.favorites) {
                    this.favoriteItems = JSON.parse(userSettings?.favorites);
                }
            }

            const groupCriteria = new Criteria();
            groupCriteria.addFilter(Criteria.equals('active', 1));
            groupCriteria.addSorting(Criteria.sort('position', 'ASC'));
            groupCriteria.addAssociation('statistics');
            groupCriteria.addFilter(Criteria.equals('statistics.active', 1));
            if (!this.hasPickwareErpPro) {
                groupCriteria.addFilter(Criteria.not('AND', [Criteria.equals('name', 'pwreturn')]));
            }

            this.groupRepository.search(groupCriteria).then((result) => {
                this.getTree(result);

            }).catch((err) => {
                this.isLoading = false;
            });
        },

        testForPickware() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('active', 1));
            criteria.addFilter(Criteria.equals('name', 'PickwareErpPro'));
            return this.pluginRepository.search(criteria).then((result) => {
                return !!result.first();

            }).catch((err) => {
                return false;
            });
        },

        getTree(groups) {
            const treeItems = [];
            let i = 0, j;

            const statisticsCriteria = new Criteria();
            statisticsCriteria.addFilter(Criteria.equals('active', 1));
            statisticsCriteria.addFilter(Criteria.equals('name', 'quick_overview'));

            this.statisticsRepository.search(statisticsCriteria).then((result) => {
                // erster Punkt der Navigation quick overview
                if (result.length > 0) {
                    treeItems.push({
                        data: result[0],
                        id: i+1,
                        name: this.$tc(result[0].label),
                        childCount: 0,
                        active: true,
                        parentId: null,
                        afterId: null
                    });
                    i++;
                    treeItems.push({
                        data: { active:true },
                        id: i+1,
                        name: this.$tc('cbax-analytics.general.favorites'),
                        childCount: this.favoriteItems.length,
                        active: false,
                        parentId: null,
                        afterId: i,
                        initialOpened: true
                    });
                    i++;
                    if (this.favoriteItems.length > 0) {
                        this.favoriteItems.forEach((item) => {
                            treeItems.push({
                                data: item.data,
                                id: i+1,
                                name: item.name,
                                childCount: 0,
                                active: false,
                                parentId: 2,
                                afterId: i
                            });
                            i++;
                        });
                    }
                }
                // Gruppen der Navigation
                if (groups.length > 0) {
                    groups.forEach((group) => {
                        if (group.statistics && group.statistics.length > 0) {
                            let stats = group.statistics.filter(stat => stat.active === 1);
                            stats.sort((a, b) => a.position - b.position);
                            group.statistics = stats;
                        }
                        if (group.statistics && group.statistics.length > 0) {
                            let parentId = i+1;
                            if (i === 0) {
                                j = null;
                            } else {
                                j = i;
                            }
                            treeItems.push({
                                data: group,
                                id: i+1,
                                name: this.$tc(group.label),
                                childCount: group.statistics.length,
                                active: false,
                                parentId: null,
                                afterId: j
                            });
                            i++;
                            // Statistiken in den Gruppen
                            group.statistics.sort((a, b) => a.position - b.position)
                            group.statistics.forEach((item) => {
                                treeItems.push({
                                    data: item,
                                    id: i+1,
                                    name: this.$tc(item.label),
                                    childCount: 0,
                                    active: false,
                                    parentId: parentId,
                                    afterId: i
                                });
                                i++;
                            });
                        }
                    });
                }

                this.$nextTick(() => {
                    this.treeItems = treeItems;
                    if (treeItems[0].data.name === 'quick_overview') {
                        this.$emit('cbax-statistic-selection', treeItems[0]);
                    }
                    this.isLoading = false;
                });
            }).catch((err) => {
                this.isLoading = false;
            });
        },

        changeSelectedStatistic(element) {
            this.statisticId = element.data.id.toString();

            if (element.data.data.name === 'quick_overview') {
                this.treeItems[0].active = true;
            } else if (element.parentId) {
                this.treeItems[0].active = false;
            }

            if (element.parentId || element.data.data.name === 'quick_overview') {
                this.$emit('cbax-statistic-selection', element.data);

                let allStats = document.querySelectorAll('.sw-tree-item .is--no-children');
                allStats.forEach((item) => {
                    if (item.querySelector('span.sw-tree-item__label') && item.querySelector('span.sw-tree-item__label').innerHTML === element.data.name) {
                        item.classList.add('is--active');
                    } else {
                        if (item.classList.contains('is--active')) {
                            item.classList.remove('is--active');
                        }
                    }
                });

                //this.$refs['treeGroup' + element.parentId][0].item.children.filter(stat => stat.id === element.id)[0].active = true;
                //this.$refs['treeGroup' + element.parentId][0].item.children.filter(stat => stat.id === element.id)[0].activeElementId = element.id;
                /** das funktioniert nur für oberste Ebene
                this.treeItems.forEach((item, index) => {
                    if (item.id === element.data.id) {
                        this.treeItems[index].active = true;
                    } else {
                        this.treeItems[index].active = false;
                    }
                });
                 *
                 */

            } else {
                this.$refs['treeGroup' + element.id][0].openTreeItem();
                this.$refs['treeGroup' + element.id][0].getTreeItemChildren(element);
            }
        },

        unsetStatistics() {
            if (this.activeStatisticName === '') {
                this.treeItems.forEach((item) => {
                    item.active = false;
                });
            }
        },

        addFavorite(item) {
            //Statistik schon Favorite
            if (this.favoriteItems.find((el) => el.data.id === item.data.data.id) !== undefined) return;
            this.favoriteItems.push(item.data);

            this.$nextTick(() => {
                try {
                    this.saveUserSettings(
                        USER_CONFIG_KEY,
                        { favorites: JSON.stringify(this.favoriteItems) },
                        this.currentUser?.id
                    );
                } catch(e) {

                }

                this.getList(false);
            });
        },

        deleteFavorite(item) {
            this.favoriteItems = this.favoriteItems.filter((stat) => stat.name !== item.data.name);

            this.$nextTick(() => {
                try {
                    this.saveUserSettings(
                        USER_CONFIG_KEY,
                        { favorites: JSON.stringify(this.favoriteItems) },
                        this.currentUser?.id
                    );
                } catch (e) {

                }

                this.getList(false);
            });
        }

    }
});
