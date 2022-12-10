<?php
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
const TbSearchForm = {
    data() {
        return {
            restaurant_id: this.restaurant,
            restaurants: []
        }
    },
    methods: {
        submit() {
            console.log(this.restaurant);
        },
        populateRestaurant() {
            if (this.restaurants.length != 0) {
                return;
            }
            this.restaurants = [{id: 1, name: "coco"}, {id: 2, name: "jambo"}];
        },
        setRestaurant(id) {
            this.restaurant_id = id;
        },
    },
    props: ["restaurant"],
    template: `
        <div class="form-group">

            <select v-if="this.restaurant == 0"
                @click="populateRestaurant()">
                <option value="0" @click="setRestaurant(0)">
                    <?php echo JText::_('PLG_CONTENT_TABLEBOOKING_SELECT_RESTAURANT', true);?>
                </option>
                <option v-for="restaurant in this.restaurants"
                    :value="restaurant.id"
                    :key="restaurant.id"
                    @click="setRestaurant(restaurant.id)">{{ restaurant.name }}</option>
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