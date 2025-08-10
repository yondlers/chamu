<?php

namespace App\Helpers;

use Whoops\Util\TemplateHelper;

class HtmlForm
{

    public static function generateDisclaimer($name, $class){
        return "<div class=\"$class\" >$name</div>";
    }

    public static function generateLineBreaker() {
        return '<hr class="mt-4 mb-4">';
    }

    public static function generateImage($base64) {
        if (!$base64) {
            return "";
        }
        return "
            <div class='justify-items-center'>
                <img src=\"$base64\" width='600' height='300'/>
            </div>";
    }

    public static function generateInput($name, $type, $value, $required, $disabled = false) {
        // Format the label by replacing underscores with spaces, capitalizing each word, and removing trailing '_id'
        $label = ucwords(str_replace('_', ' ', preg_replace('/_id$/', '', $name)));

        // Determine if the input is required
        $requiredAttribute = $required ? 'required' : '';

        // Generate placeholder if not provided
        $placeholder = 'Enter ' . strtolower(strip_tags($label));

        // Append asterisk to the label if the input is required
        $label .= $required ? ' <span class="text-red-500">*</span>' : '';

        // Ensure $value is a string
        $value = is_array($value) ? implode(', ', $value) : htmlspecialchars((string)$value, ENT_QUOTES);

        $inputHolder = $name . '_hold';

        return "
        <div class=\"space-y-1\" id=\"$inputHolder\">
            <label for=\"$name\" class=\"block text-sm font-medium text-gray-700\">$label</label>
            <input type=\"$type\" id=\"$name\" name=\"$name\" class=\"w-full mt-1 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-300 focus:outline-none border-gray-300\" placeholder=\"$placeholder\" value=\"$value\" $requiredAttribute $disabled >
        </div>
    ";
    }


    public static function generateSelect($name, $options, $selected = null, $required = true, $disabled = false)
    {
        // Format the label by replacing underscores with spaces, capitalizing each word, and removing trailing '_id'
        $label = ucwords(str_replace('_', ' ', preg_replace('/_id$/', '', $name)));

        // Generate placeholder if not empty
        if (empty($options))
        {
            $placeholder = 'Please add ' . strtolower(strip_tags($label));
        } else
        {
            $placeholder = 'Select ' . strtolower(strip_tags($label));
        }


        // Append asterisk to the label if the input is required
        $label .= $required ? ' <span class="text-red-500">*</span>' : '';

        $inputHolder = $name . '_hold';

        // Determine if the input is required
        $requiredAttribute = $required ? 'required' : '';
        $disabledAttribute = $disabled ? ' disabled' : '';

        $input = "<div class=\"space-y-1\" id=\"$inputHolder\">
                <label for=\"$name\" class=\"block text-sm font-medium text-gray-700\">$label</label>
                <select id=\"$name\" name=\"$name\" class=\"w-full mt-1 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-300 focus:outline-none border-gray-300\" $requiredAttribute $disabledAttribute >
                    <option value=\"\">$placeholder</option>";

        foreach ($options as $key => $value) {
            // Handle key-value pairs directly
            if (is_scalar($key) && is_scalar($value)) {
                $isSelected = (string)$key === (string)$selected ? 'selected' : '';
                $input .= "<option value=\"$key\" $isSelected>$value</option>";
            }
            // Handle objects or arrays with 'id' and 'name'
            elseif (is_object($value)) {
                $key = $value->id;
                $name = $value->name;
                $isSelected = (string)$key === (string)$selected ? 'selected' : '';
                $input .= "<option value=\"$key\" $isSelected>$name</option>";
            }
            elseif (is_array($value)) {
                $key = $value['id'];
                $name = $value['name'];
                $isSelected = (string)$key === (string)$selected ? 'selected' : '';
                $input .= "<option value=\"$key\" $isSelected>$name</option>";
            }
        }

        $input .= '</select>
            </div>';

        return $input;
    }


    public static function documentUploadInputs() {
        return '
            <div class="space-y-1">
                <label for="id_document_path" class="block text-sm font-medium text-gray-700">ID / Passport Document</label>
                <input id="id_document_path" type="file" name="id_document_path" class="w-full mt-1 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-300 focus:outline-none border-gray-300" >
            </div>

            <div class="space-y-1">
                <label for="bank_statements_path" class="block text-sm font-medium text-gray-700">Bank Statement</label>
                <input id="bank_statements_path" type="file" name="bank_statements_path" class="w-full mt-1 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-300 focus:outline-none border-gray-300" >
            </div>

            <div class="space-y-1">
                <label for="proof_of_income_path" class="block text-sm font-medium text-gray-700">Proof of Income</label>
                <input id="proof_of_income_path" type="file" name="proof_of_income_path" class="w-full mt-1 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-300 focus:outline-none border-gray-300" >
            </div>
        ';
    }


    public static function templateInput($value)
    {
        if (!$value) {
            $value = LeaseTemplates::STANDARD_LEASE_TEMPLATE;
        }

        return '
            <div class="space-y-1">
                <label for="template_html" class="block text-sm font-medium text-gray-700">Template *</label>
                <textarea id="template_html" name="template_html" class="w-full mt-1 px-4 py-2 border rounded-lg shadow-sm focus:ring focus:ring-blue-300 focus:outline-none border-gray-300" placeholder="Enter slug" required>
                    ' . $value . '
                </textarea>

            </div>
        ';
    }

    public static function submitButtonInput($display = null)
    {
        if (!$display)
        {
            $display = 'Submit';
        }
        return '
              <div>
                    <hr class="bg-red-900">
                </div>

                <!-- Submit Button -->
                <div>
                    <button
                        type="submit"
                        class="w-full block text-center card rounded-lg border bg-gray-800 dark:border-gray-700 text-white py-2"
                    >
                        ' . $display . '
                    </button>
                </div>
        ';
    }

    public static function tagsHtml()
    {
        return
        '
            <div class="space-y-1">
                <hr>
                <div class="text-center">
                    Click one of the dynamic tags to store in your Clipboard!
                </div>
            </div>

            <!-- Tag -->
            <div class="space-y-1 flex flex-wrap gap-4">
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="today_date" data-value="TODAY_DATE" onclick="copy(\'today_date\')">Today\'s Date</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="lease_start_date" data-value="LEASE_START_DATE" onclick="copy(\'lease_start_date\')">Lease Start Date</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="lease_end_date" data-value="LEASE_END_DATE" onclick="copy(\'lease_end_date\')">Lease End Date</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="landlord_name" data-value="LANDLORD_NAME" onclick="copy(\'landlord_name\')">Landlord Name</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="landlord_address" data-value="LANDLORD_ADDRESS" onclick="copy(\'landlord_address\')">Landlord Address</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="tenant_name" data-value="TENANT_NAME" onclick="copy(\'tenant_name\')">Tenant Name</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="tenant_address" data-value="TENANT_ADDRESS" onclick="copy(\'tenant_address\')">Tenant Address</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="property_address" data-value="PROPERTY_ADDRESS" onclick="copy(\'property_address\')">Property Address</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="rent_amount" data-value="RENT_AMOUNT" onclick="copy(\'rent_amount\')">Rent Amount</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="rent_due_date" data-value="RENT_DUE_DATE" onclick="copy(\'rent_due_date\')">Rent Due Date</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="payment_method" data-value="PAYMENT_METHOD" onclick="copy(\'payment_method\')">Payment Method</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="late_fee" data-value="LATE_FEE" onclick="copy(\'late_fee\')">Late Fee</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="late_fee_days" data-value="LATE_FEE_DAYS" onclick="copy(\'late_fee_days\')">Late Fee Days</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="deposit_amount" data-value="DEPOSIT_AMOUNT" onclick="copy(\'deposit_amount\')">Deposit Amount</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="key_deposit_amount" data-value="KEY_DEPOSIT_AMOUNT" onclick="copy(\'key_deposit_amount\')">Key Deposit Amount</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="utility_deposit_amount" data-value="UTILITY_DEPOSIT_AMOUNT" onclick="copy(\'utility_deposit_amount\')">Utility Deposit Amount</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="damage_deposit_amount" data-value="DAMAGE_DEPOSIT_AMOUNT" onclick="copy(\'damage_deposit_amount\')">Damage Deposit Amount</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="furniture_deposit_amount" data-value="FURNITURE_DEPOSIT_AMOUNT" onclick="copy(\'furniture_deposit_amount\')"> Furniture Deposit Amount</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="landlord_signature" data-value="LANDLORD_SIGNATURE" onclick="copy(\'landlord_signature\')">Landlord Signature</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="electricity_payer" data-value="ELECTRICITY_PAYER" onclick="copy(\'electricity_payer\')">Electricity Payer</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="water_payer" data-value="WATER_PAYER" onclick="copy(\'water_payer\')">Water Payer</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="gas_payer" data-value="GAS_PAYER" onclick="copy(\'gas_payer\')">Gas Payer</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="trash_payer" data-value="TRASH_PAYER" onclick="copy(\'trash_payer\')">Trash Payer</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="internet_payer" data-value="INTERNET_PAYER" onclick="copy(\'internet_payer\')">Internet Payer</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="supply_payer" data-value="SUPPLY_PAYER" onclick="copy(\'supply_payer\')">Supply Payer</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="landlord_signature" data-value="LANDLORD_SIGNATURE" onclick="copy(\'landlord_signature\')">Landlord Signature</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="tenant_signature" data-value="TENANT_SIGNATURE" onclick="copy(\'tenant_signature\')">Tenant Signature</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="tenant_signature_date" data-value="TENANT_SIGNATURE_DATE" onclick="copy(\'tenant_signature_date\')">Tenant Signature Date</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="number_of_occupants" data-value="NUMBER_OF_OCCUPANTS" onclick="copy(\'number_of_occupants\')">Number of Occupants</button>
                <button type="button" class="w-30 p-2 block text-center card rounded-lg border bg-white dark:border-gray-700 text-black py-3" id="notice_period" data-value="NOTICE_PERIOD" onclick="copy(\'notice_period\')">Notice Period</button>
            </div>
        ';
    }


    public static function signature() {
        return '
            <div class="space-y-1">
                <label>Signature *</label>
                <div class="justify-items-center">
                    <div style="background-color: darkgray; border-radius: 10px;">
                        <canvas id="signatureCanvas" width="600" height="300"></canvas>
                    </div>
                    <br>
                    <a id="saveButton" class="  text-center card rounded-lg border bg-gray-800 dark:border-gray-700 text-white py-3">Save Signature</a>
                    <a id="clearButton" class="  text-center card rounded-lg border bg-gray-800 dark:border-gray-700 text-white py-3">Clear Signature</a>
                </div>
                <input type="hidden" name="signature_image" id="signature_image">
            </div>
        ';
    }

//    public

}

