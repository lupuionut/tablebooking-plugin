<?php

use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

$key = $displayData['key'];
$restaurant_id = $displayData['id'];
?>

<div id="tb-bookingForm<?php echo $key;?>" class="bookingForm">
    <tb-search-form
        rkey="<?php echo $key;?>"
        rid="<?php echo $restaurant_id;?>"
        @submit="this.searchFormSubmit"></tb-search-form>

    <tb-choose-table
        v-if="idx == 1"
        :data="this.formData"
        :key="this.formData.timestamp"></tb-choose-table>

    <tb-input-details v-if="idx == 2"></tb-input-details>
</div>

<script type="text/javascript">
<?php if ($displayData['isNew']) {?>
const JoomlaToken = '<?php echo Session::getFormToken();?>';
const JoomlaUri = '<?php echo Uri::root();?>';

const TbSearchForm = {
    data() {
        return {
            restaurant: {
                id: this.rid,
                params: {},
                hours: [],
            },
            form: {
                restaurants: [],
                date: '',
                starthour: '',
                endhour: '',
                places: 0,
                error: ''
            },
            restrictedDays: [],
        }
    },
    methods: {
        submitForm() {
            this.form.error = "";
            if (Number(this.restaurant.id) == 0) {
                this.form.error =  '<?php echo JText::_('PLG_CONTENT_TABLEBOOKING_ERROR_NO_RESTAURANT_SELECTED', false);?>';
                return;
            }
            if (!this.form.date) {
                this.form.error = '<?php echo JText::_('PLG_CONTENT_TABLEBOOKING_ERROR_NO_DATE_SELECTED', false);?>';
                return;
            }
            if (!this.form.starthour) {
                this.form.error = '<?php echo JText::_('PLG_CONTENT_TABLEBOOKING_ERROR_NO_START_TIME_SELECTED', false);?>';
                return;
            }
            if (!this.restaurant.params.booking_length && !this.form.endhour) {
                this.form.error = '<?php echo JText::_('PLG_CONTENT_TABLEBOOKING_ERROR_NO_END_TIME_SELECTED', false);?>';
                return;
            } else {
                if (Number(this.restaurant.params.booking_length) != 0) {
                    this.form.endhour = Number(this.form.starthour) + Number(this.restaurant.params.booking_length);
                }
            }
            if (this.form.places == 0) {
                this.form.error = '<?php echo JText::_('PLG_CONTENT_TABLEBOOKING_ERROR_NO_PLACES_SELECTED', false);?>';
                return;
            } else {
                const min = Number(this.restaurant.params.minplaces);
                const max = Number(this.restaurant.params.maxplaces);
                if ((min != 0 && this.form.places < min) || (max != 0 && this.form.places > max)) {
                    this.form.error =
                        '<?php echo JText::_('PLG_CONTENT_TABLEBOOKING_ERROR_PLACES_WRONG', false);?>'
                        .replace('%mn', min).replace('%mx', max);
                    return;
                }
            }
            const formData = {
                'restaurant': Number(this.restaurant.id),
                'date': this.form.date.toISOString().split('T')[0],
                'start': this.findHourKey(this.form.starthour),
                'end': this.findHourKey(this.form.endhour),
                'places': this.form.places,
                'timestamp': new Date().getTime()
            };

            if (formData.start > formData.end) {
                this.form.error = '<?php echo JText::_('PLG_CONTENT_TABLEBOOKING_ERROR_START_HOUR_LATER_END_HOUR', false);?>';
                return;
            }

            fetch(
                JoomlaUri + 'index.php?option=com_tablebooking&task=search.getRestaurantTables&'
                    + 'restaurant=' + formData.restaurant + '&'
                    + 'date=' + formData.date + '&'
                    + 'start=' + formData.start + '&'
                    + 'end=' + formData.end + '&'
                    + 'places=' + formData.places + '&'
                    + JoomlaToken + '=1'
            )
            .then(r => r.json())
            .then(r => {
                if (r.error == null) {
                    formData.tables = r.ok;
                    formData.restaurant = this.restaurant;
                    this.$emit('submit', JSON.stringify(formData));
                } else {
                    this.form.error = r.error;
                }
            });
        },
        loadRestaurant(id) {
            fetch(
                JoomlaUri + 'index.php?option=com_tablebooking&task=search.getRestaurant&id='
                + id + '&' + JoomlaToken + '=1'
            ).then(r => r.json())
            .then(r => { this.restaurant = r })
            this.form.date = '';
            this.form.starthour = 0;
            this.form.endhour = 0;
            this.form.places = '';
            this.restaurant.hours = [];
        },
        getRestaurantHours() {
            const info = {
                'restaurant': Number(this.restaurant.id),
                'date': this.form.date.toISOString().split('T')[0]
            };
            fetch(
                JoomlaUri + 'index.php?option=com_tablebooking&task=search.getWorkingHours&' +
                    'info[date]=' + info.date + '&info[restaurant]=' + info.restaurant + '&' + JoomlaToken + '=1')
            .then(r => r.json())
            .then(r => { this.restaurant.hours = this.formatHours(r.hours) })
        },
        formatHours(hours) {
            const keys = Object.keys(hours)
            const values = Object.values(hours)
            let modified = []
            keys.forEach((v,k) => {const i = {}; i.key = v; i.value = values[k]; modified.push(i);})
            return modified;
        },
        findHourKey(value) {
            const found = this.restaurant.hours.filter(e => {return e.value == value});
            if (found.length == 1) {
                return found[0].key;
            }
            return 0;
        },
        formatDate() {
            const replacements = {
                'Y': 'yyyy',
                'm': 'MM',
                'd': 'dd',
                'M': 'MMM',
                'F': 'MMMM',
                'j': 'd',
                'n': 'L'
            }
            if (this.restaurant.params.dateformat) {
                return this.restaurant.params.dateformat.split('').map(c => {
                    if (Object.keys(replacements).indexOf(c) !== -1) {
                        return replacements[c];
                    } else {
                        return c;
                    }
                }).join('');
            }
        },
        disabledDates(month = -1, year = -1) {
            let disabled = [];
            let restrictedWeekDays = [];

            const pattern = RegExp(/[0-9]{4}-[0-9]{2}-[0-9]{2}/);
            if (this.restaurant.params.restricted) {
                disabled = this.restaurant.params.restricted
                            .filter(e => {return e.match(pattern) != null})
                restrictedWeekDays = this.restaurant.params.restricted
                    .filter(e => {return Number(e) < 10 })
                    .map(e => {return Number(e)})
            }

            if (restrictedWeekDays) {
                const m = month == -1 ? new Date().getMonth() + 1 : month + 1;
                const y = year == -1 ? new Date().getFullYear() : year;
                for (let i=1; i<=31; i++) {
                    const d = y + '-' + m + '-' + i;
                    const date = new Date(d);
                    if (date.toString() != 'Invalid Date') {
                        const wd = date.getDay();
                        // sunday
                        if (wd == 0 && restrictedWeekDays.indexOf(7) !== -1) {
                            if (this.restaurant.params.allowed.indexOf(d) === -1) {
                                disabled.push(d);
                            }
                        } else if (restrictedWeekDays.indexOf(wd) !== -1) {
                            if (this.restaurant.params.allowed.indexOf(d) === -1) {
                                disabled.push(d);
                            }
                        }
                    }
                }
            }
            return disabled;
        },
        calendarOpen() {
            this.restrictedDays = this.disabledDates();
        },
        calendarUpdateMonthYear({ instance, month, year }) {
            this.restrictedDays = this.disabledDates(month, year);
        },
        calendarWeekSart() {
            if (this.restaurant.params.startcal) {
                return this.restaurant.params.startcal;
            }
            return 1;
        }
    },
    mounted() {
        if (this.restaurant.id == 0) {
            fetch(JoomlaUri + 'index.php?option=com_tablebooking&task=search.getRestaurants&' + JoomlaToken + '=1')
            .then(r => r.json())
            .then(r => { this.form.restaurants = r })
        } else {
            this.loadRestaurant(this.restaurant.id);
        }
    },
    props: ["rid", "rkey"],
    components: {
        Datepicker: VueDatePicker
    },
    template: `
        <div class="form-group tb-plugin-form first-step">
            <div class="tb-plugin-form-row tb-plugin-error" v-if="this.form.error">
                <span class="tb-close" @click="this.form.error = ''">X</span>
                {{form.error}}
            </div>
            <div class="tb-plugin-form-row">
                <select
                    v-if="this.rid == 0"
                    v-model="this.restaurant.id"
                    @change="this.loadRestaurant(this.restaurant.id)">
                    <option value="0">
                        <?php echo JText::_('PLG_CONTENT_TABLEBOOKING_SELECT_RESTAURANT', true);?>
                    </option>
                    <option v-for="restaurant in this.form.restaurants"
                        :value="restaurant.value"
                        :key="restaurant.value">{{ restaurant.name }}</option>
                </select>
            </div>

            <div class="tb-plugin-form-row">
                <datepicker
                    v-if="this.restaurant.id != 0"
                    v-model="this.form.date"
                    @open="this.calendarOpen"
                    @updateMonthYear="this.calendarUpdateMonthYear"
                    @update:model-value="this.getRestaurantHours()"
                    auto-apply
                    :enable-time-picker="false"
                    :close-on-auto-apply="true"
                    :min-date="new Date()"
                    :disabled-dates="this.restrictedDays"
                    :format="this.formatDate()"
                    :week-start="this.calendarWeekSart()"></datepicker>

                <div class="row-inline" v-if="this.restaurant.id != 0">
                    <select
                        v-model="this.form.starthour">
                        <option value="0">
                            <?php echo JText::_('PLG_CONTENT_TABLEBOOKING_FROM_HOUR', false);?>
                        </option>
                        <option
                            v-for="item in this.restaurant.hours"
                            :key="item.key">{{item.value}}</option>
                    </select>
                </div>

                <div class="row-inline" v-if="this.restaurant.id != 0 && Number(this.restaurant.params.booking_length) == 0">
                    <select
                        v-model="this.form.endhour">
                        <option value="0">
                            <?php echo JText::_('PLG_CONTENT_TABLEBOOKING_TO_HOUR', false);?>
                        </option>
                        <option
                            v-for="item in this.restaurant.hours"
                            :key="item.key">{{item.value}}</option>
                    </select>
                </div>

                <div class="row-inline" v-if="this.restaurant.id != 0">
                    <input
                        type="text"
                        placeholder="<?php echo JText::_('PLG_CONTENT_TABLEBOOKING_NUMBER_PERSONS', false);?>"
                        v-if="this.restaurant.id != 0"
                        v-model="this.form.places" />
                </div>

                <div class="row-inline">
                    <button type="button"
                        @click="this.submitForm()">
                        <?php echo JText::_('PLG_CONTENT_TABLEBOOKING_SEARCH', true);?></button>
                </div>
            </div>
        </div>
    `
};

const TbChooseTable = {
    data() {
        return {
            chartDimensions: this.getChartDimensions(),
            tables: {
                available: this.getAvailableTables(),
                selected: this.getSelectedTables()
            }
        }
    },
    methods: {
        getChartDimensions() {
            const chartDimensions = {x: 0, y: 0};
            if (this.data.tables.total.length > 0) {
                this.data.tables.total.map(
                    e => {return e.params}
                ).map(
                    e => {
                        if (e !== "") {
                            return e.split("x");
                        } else {
                            return [0,0];
                        }
                    }
                ).map(
                    e => {
                        if (e[0] > chartDimensions.y) {
                            chartDimensions.y = e[0]
                        }
                        if (e[1] > chartDimensions.x) {
                            chartDimensions.x = e[1];
                        }
                    }
                )
            }
            return chartDimensions;
        },
        getAvailableTables() {
            const available = new Map();
            this.data.tables.total.forEach(t => {
                if (this.data.tables.booked.indexOf(String(t.id)) === -1) {
                    available.set(t.id, t);
                }
            });
            return available;
        },
        getSelectedTables() {
            const selected = new Map();
            const total = this.getAvailableTables();
            const autoselected = this.data.tables.selected.split(",");
            if (autoselected.length > 0) {
                autoselected.forEach(t => {
                    selected.set(t, total.get(t));
                })
            }
            return selected;
        },
        getNonPositionedTables() {
            return this.data.tables.total.filter(e => {return e.params == "";});
        },
        getPositionedTables() {
            const tables = new Map();
            this.data.tables.total.filter(e => {return e.params != ""}).map(e => {tables.set(e.params, e);});
            return tables;
        },
        getTableAt(line, column) {
            const tables = this.getPositionedTables();
            const coordinates = String((Number(line) - 1)) + 'x' + String(Number(column) - 1);
            if (!tables.has(coordinates)) {
                return undefined;
            }
            return tables.get(coordinates);
        },
        getNumberOfPlaces(table) {
            if (table == undefined) {
                return '';
            }
            return table.min_places + ' - ' + table.max_places;
        },
        getClassForTable(table) {
            if (table == undefined) {
                return "tb-cell empty";
            }

            let clas = "tb-cell";
            clas += this.tables.available.has(table.id) ? ' available' : ' booked';
            if (table.shape == 1) {
                clas += ' circle';
            } else if (table.shape == 2) {
                clas += ' rectangle';
            } else {
                clas += ' square';
            }
            if (this.tables.selected.has(table.id)) {
                clas += ' selected';
            }
            return clas;
        },
        chooseTable(event, table) {
            if (!this.tables.available.has(table.id)) {
                return;
            }
            if (this.tables.selected.has(table.id)) {
                this.tables.selected.delete(table.id);
            } else {
                let total = 0;
                if (this.data.places < table.min_places) {
                    return;
                }
                this.tables.selected.forEach(t => {total += Number(t.max_places);});
                if (total < this.data.places) {
                    this.tables.selected.set(table.id, table);
                }
            }
        },
    },
    mounted() {
    },
    props: ["data", "key"],
    template: `
        <div class="tb-choose-table">
            <h3><?php echo JText::_('PLG_CONTENT_TABLEBOOKING_CHOOSE_TABLE', true);?></h3>
            <div v-for="table in this.getNonPositionedTables()" class="tb-line">
                <span
                    :class="this.getClassForTable(table)"
                    @click="this.chooseTable($event, table)">
                    <i>{{ getNumberOfPlaces(table) }}</i>
                </span>
            </div>
            <div v-for="line in Number(this.chartDimensions.y) + 1" class="tb-line">
                <div v-for="column in Number(this.chartDimensions.x) + 1" class="tb-dropzone">
                    <span
                        :class="this.getClassForTable(this.getTableAt(line, column))"
                        @click="this.chooseTable($event, this.getTableAt(line, column))">
                        <i>{{ getNumberOfPlaces(this.getTableAt(line, column)) }}</i>
                    </span>
                </div>
            </div>
        </div>
    `
};

const TbInputDetails = {
    data() {
        return {
        }
    },
    methods: {

    },
    template: `
        <div class="tb-input-details">Input details</div>
    `
};
<?php } ?>

Vue.createApp({
    data() {
        return {
            idx: 0,
            formData: {},
        }
    },
    methods: {
        searchFormSubmit(e) {
            this.formData = JSON.parse(e);
            if (this.formData.restaurant.params.allow_user_choose_table == 1) {
                this.idx = 1;
            } else {
                this.idx = 2;
            }
        }
    },
    components: {
        TbSearchForm,
        TbChooseTable,
        TbInputDetails
    },
}).mount('#tb-bookingForm<?php echo $key;?>')
</script>