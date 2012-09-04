<?php
//  vim:ts=4:et

//  Copyright (c) 2011, LoveMachine Inc.
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
<?php 
// Already included in signup form don't repeat
if ( empty($signup)) { ?>
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
                    <div id="cityDiv">
                        <p><label for="City">City</label><br />
                        <span class="required-bullet">*</span> <input type="text" id="city" name="city" class="text-field"
                            size="35"
                            value="<?php echo isset($userInfo['city']) ? $userInfo['city'] : (isset($_REQUEST['city'])?$_REQUEST['city']:''); ?>" />
                        </p>
                    </div>
<?php } ?>                    
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

                            <span id="phone_wrapper">
                                <span id="phone_prefix"></span>
                                <input type="text" name="phone" id="phone" size="30" value="<?php echo $phone ?>" />&nbsp;
                            </span>
                            <?php if (isset($settingsPage)) { ?>
                            <a id="send-test" href="#">send test text</a>
                            <?php } ?>

                        <div id="sms-other" <?php echo ((empty($provider) || $provider{0}!='+')?'style="display:none"':'') ?>>
                            <p><label>SMS Address</label><br />
                            <input type="text" id="smsaddr" name="smsaddr" size="35" value="<?php echo $smsaddr; ?>" /><br/>
                            <br/>
                            <em id="sms_helper">Please enter your email address or mobile phone number for sending sms messages.</em>
                            </p>
                        </div>
                    </div>
                    <script type="text/javascript">
                        // TODO: Move this inline javascript to header, or external file
                        var city = new LiveValidation('city', {validMessage: "Valid city."});
                        city.add(Validate.Presence);
                        var phone = new LiveValidation('phone');
                        phone.add(Validate.Custom, { against: validatePhoneNumber });
                        var smsaddr = new LiveValidation('smsaddr', {validMessage: "Valid SMS email address."});
                        smsaddr.add(Validate.Custom, { against: validateSmsAddr, failureMessage: "Invalid SMS email address!" });
                    </script>

                </div>
