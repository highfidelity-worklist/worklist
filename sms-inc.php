<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//
?>
<?php
/**
 * Sms_Numberlist
 */
require_once 'lib/Sms/Numberlist.php';
?>
                <div id="sms" >

                    <div id="sms-country">
                        <p><label>Country<br />
                        <span class="required-bullet">*</span> <select id="country" name="country" style="width:274px">
                            <?php
                            if (empty($country) || $country == '--') {
                                //$selected not set by this point, we want to default so do that
                                echo '<option value="">Where do you live?</option>';
                            }
                            foreach ($countrylist as $code=>$cname) {
                                $selected = ($country == $code) ? "selected=\"selected\"" : "";
                                echo '<option value="'.$code.'" '.$selected.'>'.$cname.'</option>';
                            }
                            ?>
                        </select>
                        </label><br/>
                        </p>
                    </div>
                    <div id="sms-number">
                        <label>Mobile device number<br /></label>
                            <select id="int_code" name="int_code">
                                <option value="">International Code</option>
                                <?php foreach (Sms_Numberlist::$codeList as $codeDescription=>$code) { ?>
                                <option value="<?php echo $code;?>"<?php echo ($int_code == $code) ? ' selected="selected"' : ''; ?>>
                                    <?php echo $codeDescription . ' (+' . $code . ')'; ?>
                                </option>
                                <?php } ?>
                            </select>
                            <input name="phone_edit" type="hidden" id="phone_edit" value="0"/>

                            <input name="stored-provider" type="hidden" id="stored-provider" value="<?php echo $provider; ?>" />
                            <select id="provider" name="provider" <?php echo ((empty($country) || $country == '--')?'style="display:none"':'') ?> style="width:274px">
                                <?php if (empty($country) || $country == '--') { ?>
                                <option value="Select Country">Please select a Country</option>
                                <?php } else { ?>
                                <option value="Wireless Provider">(Other)</option>
                                <?php } ?>
                            </select>

                            <input type="text" name="phone" id="phone" size="15" value="<?php echo $phone ?>" />&nbsp;
                            <?php if (isset($settingsPage)) { ?>
                            <a id="send-test" href="#">send test text</a>
                            <?php } ?>

                        <div id="sms-other" <?php echo ((empty($provider) || $provider{0}!='+')?'style="display:none"':'') ?>>
                            <p><label>SMS Address<br />
                            <input type="text" id="smsaddr" name="smsaddr" size="35" value="<?php echo (!empty($smsaddr)?$smsaddr:((!empty($provider) && $provider{0} == '+')?substr($provider, 1):'')) ?>" />
                            </label><br/>
                            <em id="sms_helper">Please enter the email address for sending text messages.</em>
                            </p>
                        </div>
                    </div>

                        <blockquote>
                            <input type="checkbox" id="journal_alerts" name="journal_alerts" value="1" <?php if ($sms_flags & SMS_FLAG_JOURNAL_ALERTS) echo 'checked' ?> />Forward Journal alerts as text messages<br />
                            <input type="checkbox" id="bid_alerts" name="bid_alerts" value="1" <?php if ($sms_flags & SMS_FLAG_BID_ALERTS) echo 'checked' ?> />Forward bid changes alerts as text messages
                        </blockquote>
                </div>
