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
        restaurant="<?php echo $restaurant_id;?>"></tb-search-form>

</div>

<script type="text/javascript">
<?php if ($displayData['isNew']) {?>
const JoomlaToken = '<?php echo Session::getFormToken();?>';
const JoomlaUri = '<?php echo Uri::root();?>';
const TbSearchForm = {
    data() {
        return {
            restaurant_id: this.restaurant,
            form: {
                restaurants: []
            }
        }
    },
    methods: {
        submit() {
            console.log(this.restaurant_id);
        }
    },
    mounted() {
        if (this.restaurant_id == 0) {
            fetch(JoomlaUri + 'index.php?option=com_tablebooking&task=search.getRestaurants&' + JoomlaToken + '=1')
            .then(r => r.json())
            .then(r => { this.form.restaurants = r })
        }
    },
    props: ["restaurant"],
    template: `
        <div class="form-group">

            <select v-if="this.restaurant == 0"
                v-model="this.restaurant_id">
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