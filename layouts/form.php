<?php
defined('_JEXEC') or die;
$key = $displayData['key'];
?>

<div id="tb-bookingForm<?php echo $key;?>" class="bookingForm">
    <tb-search-form v-if="idx == 0"></tb-search-form>
</div>

<script type="text/javascript">

<?php if ($displayData['isNew']) {?>
const TbSearchForm = {
    data() {
        return {

        }
    },
    template: `
        <div class="form-group">
            <input type="text"
                name="tbcalendar"
                id="tbcalendar" />

            <input type="text"
                name="tbfrom"
                id="tbfrom" />

            <input type="text"
                name="tbto"
                id="tbto" />

            <button type="button">
                <?php echo JText::_('PLG_CONTENT_TABLEBOOKING_SEARCH');?></button>
        </div>
    `
};
<?php } ?>

Vue.createApp({
    data() {
        return {
            idx: 0,
            message: "",
        }
    },
    components: {
        TbSearchForm
    },
}).mount('#tb-bookingForm<?php echo $key;?>')
</script>