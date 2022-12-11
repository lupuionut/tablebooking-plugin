<?php

use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

$key = $displayData['key'];
$restaurant_id = $displayData['id'];
?>

<div id="tb-bookingForm<?php echo $key;?>" class="bookingForm">
    <tb-search-form
        v-if="idx == 0"
        rkey="<?php echo $key;?>"
        rid="<?php echo $restaurant_id;?>"></tb-search-form>

</div>

<script type="text/javascript">
<?php if ($displayData['isNew']) {?>
const JoomlaToken = '<?php echo Session::getFormToken();?>';
const JoomlaUri = '<?php echo Uri::root();?>';

// keep track of rendered calendars
let RenderedCalendars = [];
const TbNewCalendar = (key, r) => {
    restaurant = JSON.parse(r);
    if (RenderedCalendars[key] == undefined) {

    }
};

const TbSearchForm = {
    data() {
        return {
            restaurant: {
                id: this.rid,
                params: {}
            },
            form: {
                restaurants: [],

            }
        }
    },
    methods: {
        submit() {
            TbNewCalendar(this.rkey, JSON.stringify(this.restaurant));
        },
        loadRestaurant(id) {
            fetch(
                JoomlaUri + 'index.php?option=com_tablebooking&task=search.getRestaurant&id='
                + id + '&' + JoomlaToken + '=1'
            ).then(r => r.json())
            .then(r => { this.restaurant = r })
        },
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
    template: `
        <div class="form-group">
            <select v-if="this.rid == 0"
                v-model="this.restaurant.id"
                @change="this.loadRestaurant(this.restaurant.id)">
                <option value="0">
                    <?php echo JText::_('PLG_CONTENT_TABLEBOOKING_SELECT_RESTAURANT', true);?>
                </option>
                <option v-for="restaurant in this.form.restaurants"
                    :value="restaurant.value"
                    :key="restaurant.value">{{ restaurant.name }}</option>
            </select>

            <input type="text"
                name="tbcalendar"
                id="tbcalendar" />

            <input type="text"
                name="tbfrom"
                id="tbfrom" />

            <input type="text"
                name="tbto"
                id="tbto" />

            <button type="button" @click="submit">
                <?php echo JText::_('PLG_CONTENT_TABLEBOOKING_SEARCH', true);?></button>
        </div>
    `
};
<?php } ?>

Vue.createApp({
    data() {
        return {
            idx: 0,
        }
    },
    components: {
        TbSearchForm
    },
}).mount('#tb-bookingForm<?php echo $key;?>')
</script>