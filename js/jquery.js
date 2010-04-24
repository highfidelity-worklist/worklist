/*************************************** LiveValidationForm class ****************************************/
/**
 * This class is used internally by LiveValidation class to associate a LiveValidation field with a form it is icontained in one
 * 
 * It will therefore not really ever be needed to be used directly by the developer, unless they want to associate a LiveValidation 
 * field with a form that it is not a child of
 */

/**
   *	handles validation of LiveValidation fields belonging to this form on its submittal
   *	
   *	@var element {HTMLFormElement} - a dom element reference to the form to turn into a LiveValidationForm
   */
var LiveValidationForm = function(element){
  this.initialize(element);
}

/**
 * namespace to hold instances
 */
LiveValidationForm.instances = {};

/**
   *	gets the instance of the LiveValidationForm if it has already been made or creates it if it doesnt exist
   *	
   *	@var element {HTMLFormElement} - a dom element reference to a form
   */
LiveValidationForm.getInstance = function(element){
  var rand = Math.random() * Math.random();
  if(!element.id) element.id = 'formId_' + rand.toString().replace(/\./, '') + new Date().valueOf();
  if(!LiveValidationForm.instances[element.id]) LiveValidationForm.instances[element.id] = new LiveValidationForm(element);
  return LiveValidationForm.instances[element.id];
}

LiveValidationForm.prototype = {
  
  /**
   *	constructor for LiveValidationForm - handles validation of LiveValidation fields belonging to this form on its submittal
   *	
   *	@var element {HTMLFormElement} - a dom element reference to the form to turn into a LiveValidationForm
   */
  initialize: function(element){
  	this.name = element.id;
    this.element = element;
    this.fields = [];
    // preserve the old onsubmit event
	this.oldOnSubmit = this.element.onsubmit || function(){};
    var self = this;
    this.element.onsubmit = function(e){
      return (LiveValidation.massValidate(self.fields)) ? self.oldOnSubmit.call(this, e || window.event) !== false : false;
    }
  },
  
  /**
   *	adds a LiveValidation field to the forms fields array
   *	
   *	@var element {LiveValidation} - a LiveValidation object
   */
  addField: function(newField){
    this.fields.push(newField);
  },
  
  /**
   *	removes a LiveValidation field from the forms fields array
   *	
   *	@var victim {LiveValidation} - a LiveValidation object
   */
  removeField: function(victim){
  	var victimless = [];
  	for( var i = 0, len = this.fields.length; i < len; i++){
		if(this.fields[i] !== victim) victimless.push(this.fields[i]);
	}
    this.fields = victimless;
  },
  
  /**
   *	destroy this instance and its events
   *
   * @var force {Boolean} - whether to force the detruction even if there are fields still associated
   */
  destroy: function(force){
  	// only destroy if has no fields and not being forced
  	if (this.fields.length != 0 && !force) return false;
	// remove events - set back to previous events
	this.element.onsubmit = this.oldOnSubmit;
	// remove from the instances namespace
	LiveValidationForm.instances[this.name] = null;
	return true;
  }
   
}// end of LiveValidationForm prototype

/*************************************** Validate class ****************************************/
/**
 * This class contains all the methods needed for doing the actual validation itself
 *
 * All methods are static so that they can be used outside the context of a form field
 * as they could be useful for validating stuff anywhere you want really
 *
 * All of them will return true if the validation is successful, but will raise a ValidationError if
 * they fail, so that this can be caught and the message explaining the error can be accessed ( as just 
 * returning false would leave you a bit in the dark as to why it failed )
 *
 * Can use validation methods alone and wrap in a try..catch statement yourself if you want to access the failure
 * message and handle the error, or use the Validate::now method if you just want true or false
 */

var Validate = {

    /**
     *	validates that the field has been filled in
     *
     *	@var value {mixed} - value to be checked
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							failureMessage {String} - the message to show when the field fails validation 
     *													  (DEFAULT: "Can't be empty!")
     */
    Presence: function(value, paramsObj){
      	var paramsObj = paramsObj || {};
    	var message = paramsObj.failureMessage || "Can't be empty!";
    	if(value === '' || value === null || value === undefined){ 
    	  	Validate.fail(message);
    	}
    	return true;
    },
    
    /**
     *	validates that the value is numeric, does not fall within a given range of numbers
     *	
     *	@var value {mixed} - value to be checked
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							notANumberMessage {String} - the message to show when the validation fails when value is not a number
     *													  	  (DEFAULT: "Must be a number!")
     *							notAnIntegerMessage {String} - the message to show when the validation fails when value is not an integer
     *													  	  (DEFAULT: "Must be a number!")
     *							wrongNumberMessage {String} - the message to show when the validation fails when is param is used
     *													  	  (DEFAULT: "Must be {is}!")
     *							tooLowMessage {String} 		- the message to show when the validation fails when minimum param is used
     *													  	  (DEFAULT: "Must not be less than {minimum}!")
     *							tooHighMessage {String} 	- the message to show when the validation fails when maximum param is used
     *													  	  (DEFAULT: "Must not be more than {maximum}!")
     *							is {Int} 					- the length must be this long 
     *							minimum {Int} 				- the minimum length allowed
     *							maximum {Int} 				- the maximum length allowed
     *                         onlyInteger {Boolean} - if true will only allow integers to be valid
     *                                                             (DEFAULT: false)
     *
     *  NB. can be checked if it is within a range by specifying both a minimum and a maximum
     *  NB. will evaluate numbers represented in scientific form (ie 2e10) correctly as numbers				
     */
    Numericality: function(value, paramsObj){
        var suppliedValue = value;
        var value = Number(value);
    	var paramsObj = paramsObj || {};
        var minimum = ((paramsObj.minimum) || (paramsObj.minimum == 0)) ? paramsObj.minimum : null;;
        var maximum = ((paramsObj.maximum) || (paramsObj.maximum == 0)) ? paramsObj.maximum : null;
    	var is = ((paramsObj.is) || (paramsObj.is == 0)) ? paramsObj.is : null;
        var notANumberMessage = paramsObj.notANumberMessage || "Must be a number!";
        var notAnIntegerMessage = paramsObj.notAnIntegerMessage || "Must be an integer!";
    	var wrongNumberMessage = paramsObj.wrongNumberMessage || "Must be " + is + "!";
    	var tooLowMessage = paramsObj.tooLowMessage || "Must not be less than " + minimum + "!";
    	var tooHighMessage = paramsObj.tooHighMessage || "Must not be more than " + maximum + "!";
        if (!isFinite(value)) Validate.fail(notANumberMessage);
        if (paramsObj.onlyInteger && (/\.0+$|\.$/.test(String(suppliedValue))  || value != parseInt(value)) ) Validate.fail(notAnIntegerMessage);
    	switch(true){
    	  	case (is !== null):
    	  		if( value != Number(is) ) Validate.fail(wrongNumberMessage);
    			break;
    	  	case (minimum !== null && maximum !== null):
    	  		Validate.Numericality(value, {tooLowMessage: tooLowMessage, minimum: minimum});
    	  		Validate.Numericality(value, {tooHighMessage: tooHighMessage, maximum: maximum});
    	  		break;
    	  	case (minimum !== null):
    	  		if( value < Number(minimum) ) Validate.fail(tooLowMessage);
    			break;
    	  	case (maximum !== null):
    	  		if( value > Number(maximum) ) Validate.fail(tooHighMessage);
    			break;
    	}
    	return true;
    },
    
    /**
     *	validates against a RegExp pattern
     *	
     *	@var value {mixed} - value to be checked
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							failureMessage {String} - the message to show when the field fails validation
     *													  (DEFAULT: "Not valid!")
     *							pattern {RegExp} 		- the regular expression pattern
     *													  (DEFAULT: /./)
     *             negate {Boolean} - if set to true, will validate true if the pattern is not matched
   *                           (DEFAULT: false)
     *
     *  NB. will return true for an empty string, to allow for non-required, empty fields to validate.
     *		If you do not want this to be the case then you must either add a LiveValidation.PRESENCE validation
     *		or build it into the regular expression pattern
     */
    Format: function(value, paramsObj){
      var value = String(value);
    	var paramsObj = paramsObj || {};
    	var message = paramsObj.failureMessage || "Not valid!";
      var pattern = paramsObj.pattern || /./;
      var negate = paramsObj.negate || false;
      if(!negate && !pattern.test(value)) Validate.fail(message); // normal
      if(negate && pattern.test(value)) Validate.fail(message); // negated
    	return true;
    },
    
    /**
     *	validates that the field contains a valid email address
     *	
     *	@var value {mixed} - value to be checked
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							failureMessage {String} - the message to show when the field fails validation
     *													  (DEFAULT: "Must be a number!" or "Must be an integer!")
     */
	 Email: function(value, paramsObj)
	 {
		 return SLEmail(value,paramsObj);
	 },
	 
    /*Email: function(value, paramsObj){
    	var paramsObj = paramsObj || {};
    	var message = paramsObj.failureMessage || "Must be a valid email address!";
    	Validate.Format(value, { failureMessage: message, pattern: /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i } );
    	return true;
    },*/
    
    /**
     *	validates the length of the value
     *	
     *	@var value {mixed} - value to be checked
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							wrongLengthMessage {String} - the message to show when the fails when is param is used
     *													  	  (DEFAULT: "Must be {is} characters long!")
     *							tooShortMessage {String} 	- the message to show when the fails when minimum param is used
     *													  	  (DEFAULT: "Must not be less than {minimum} characters long!")
     *							tooLongMessage {String} 	- the message to show when the fails when maximum param is used
     *													  	  (DEFAULT: "Must not be more than {maximum} characters long!")
     *							is {Int} 					- the length must be this long 
     *							minimum {Int} 				- the minimum length allowed
     *							maximum {Int} 				- the maximum length allowed
     *
     *  NB. can be checked if it is within a range by specifying both a minimum and a maximum				
     */
    Length: function(value, paramsObj){
    	var value = String(value);
    	var paramsObj = paramsObj || {};
        var minimum = ((paramsObj.minimum) || (paramsObj.minimum == 0)) ? paramsObj.minimum : null;
    	var maximum = ((paramsObj.maximum) || (paramsObj.maximum == 0)) ? paramsObj.maximum : null;
    	var is = ((paramsObj.is) || (paramsObj.is == 0)) ? paramsObj.is : null;
        var wrongLengthMessage = paramsObj.wrongLengthMessage || "Must be " + is + " characters long!";
    	var tooShortMessage = paramsObj.tooShortMessage || "Must not be less than " + minimum + " characters long!";
    	var tooLongMessage = paramsObj.tooLongMessage || "Must not be more than " + maximum + " characters long!";
    	switch(true){
    	  	case (is !== null):
    	  		if( value.length != Number(is) ) Validate.fail(wrongLengthMessage);
    			break;
    	  	case (minimum !== null && maximum !== null):
    	  		Validate.Length(value, {tooShortMessage: tooShortMessage, minimum: minimum});
    	  		Validate.Length(value, {tooLongMessage: tooLongMessage, maximum: maximum});
    	  		break;
    	  	case (minimum !== null):
    	  		if( value.length < Number(minimum) ) Validate.fail(tooShortMessage);
    			break;
    	  	case (maximum !== null):
    	  		if( value.length > Number(maximum) ) Validate.fail(tooLongMessage);
    			break;
    		default:
    			throw new Error("Validate::Length - Length(s) to validate against must be provided!");
    	}
    	return true;
    },
    
    /**
     *	validates that the value falls within a given set of values
     *	
     *	@var value {mixed} - value to be checked
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							failureMessage {String} - the message to show when the field fails validation
     *													  (DEFAULT: "Must be included in the list!")
     *							within {Array} 			- an array of values that the value should fall in 
     *													  (DEFAULT: [])	
     *							allowNull {Bool} 		- if true, and a null value is passed in, validates as true
     *													  (DEFAULT: false)
     *             partialMatch {Bool} 	- if true, will not only validate against the whole value to check but also if it is a substring of the value 
     *													  (DEFAULT: false)
     *             caseSensitive {Bool} - if false will compare strings case insensitively
     *                          (DEFAULT: true)
     *             negate {Bool} 		- if true, will validate that the value is not within the given set of values
     *													  (DEFAULT: false)			
     */
    Inclusion: function(value, paramsObj){
    	var paramsObj = paramsObj || {};
    	var message = paramsObj.failureMessage || "Must be included in the list!";
      var caseSensitive = (paramsObj.caseSensitive === false) ? false : true;
    	if(paramsObj.allowNull && value == null) return true;
      if(!paramsObj.allowNull && value == null) Validate.fail(message);
    	var within = paramsObj.within || [];
      //if case insensitive, make all strings in the array lowercase, and the value too
      if(!caseSensitive){ 
        var lowerWithin = [];
        for(var j = 0, length = within.length; j < length; ++j){
        	var item = within[j];
          if(typeof item == 'string') item = item.toLowerCase();
          lowerWithin.push(item);
        }
        within = lowerWithin;
        if(typeof value == 'string') value = value.toLowerCase();
      }
    	var found = false;
    	for(var i = 0, length = within.length; i < length; ++i){
    	  if(within[i] == value) found = true;
        if(paramsObj.partialMatch){ 
          if(value.indexOf(within[i]) != -1) found = true;
        }
    	}
    	if( (!paramsObj.negate && !found) || (paramsObj.negate && found) ) Validate.fail(message);
    	return true;
    },
    
    /**
     *	validates that the value does not fall within a given set of values
     *	
     *	@var value {mixed} - value to be checked
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							failureMessage {String} - the message to show when the field fails validation
     *													  (DEFAULT: "Must not be included in the list!")
     *							within {Array} 			- an array of values that the value should not fall in 
     *													  (DEFAULT: [])
     *							allowNull {Bool} 		- if true, and a null value is passed in, validates as true
     *													  (DEFAULT: false)
     *             partialMatch {Bool} 	- if true, will not only validate against the whole value to check but also if it is a substring of the value 
     *													  (DEFAULT: false)
     *             caseSensitive {Bool} - if false will compare strings case insensitively
     *                          (DEFAULT: true)			
     */
    Exclusion: function(value, paramsObj){
      var paramsObj = paramsObj || {};
    	paramsObj.failureMessage = paramsObj.failureMessage || "Must not be included in the list!";
      paramsObj.negate = true;
    	Validate.Inclusion(value, paramsObj);
      return true;
    },
    
    /**
     *	validates that the value matches that in another field
     *	
     *	@var value {mixed} - value to be checked
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							failureMessage {String} - the message to show when the field fails validation
     *													  (DEFAULT: "Does not match!")
     *							match {String} 			- id of the field that this one should match						
     */
    Confirmation: function(value, paramsObj){
      	if(!paramsObj.match) throw new Error("Validate::Confirmation - Error validating confirmation: Id of element to match must be provided!");
    	var paramsObj = paramsObj || {};
    	var message = paramsObj.failureMessage || "Does not match!";
    	var match = paramsObj.match.nodeName ? paramsObj.match : document.getElementById(paramsObj.match);
    	if(!match) 
		throw new Error("Validate::Confirmation - There is no reference with name of, or element with id of '" + paramsObj.match + "'!");
    	if(value != match.value){ 
    	  	Validate.fail(message);
    	}
    	return true;
    },
    
    /**
     *	validates that the value is true (for use primarily in detemining if a checkbox has been checked)
     *	
     *	@var value {mixed} - value to be checked if true or not (usually a boolean from the checked value of a checkbox)
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							failureMessage {String} - the message to show when the field fails validation 
     *													  (DEFAULT: "Must be accepted!")
     */
    Acceptance: function(value, paramsObj){
      	var paramsObj = paramsObj || {};
    	var message = paramsObj.failureMessage || "Must be accepted!";
    	if(!value){ 
    	  	Validate.fail(message);
    	}
    	return true;
    },
    
	 /**
     *	validates against a custom function that returns true or false (or throws a Validate.Error) when passed the value
     *	
     *	@var value {mixed} - value to be checked
     *	@var paramsObj {Object} - parameters for this particular validation, see below for details
     *
     *	paramsObj properties:
     *							failureMessage {String} - the message to show when the field fails validation
     *													  (DEFAULT: "Not valid!")
     *							against {Function} 			- a function that will take the value and object of arguments and return true or false 
     *													  (DEFAULT: function(){ return true; })
     *							args {Object} 		- an object of named arguments that will be passed to the custom function so are accessible through this object within it 
     *													  (DEFAULT: {})
     */
	Custom: function(value, paramsObj){
		var paramsObj = paramsObj || {};
		var against = paramsObj.against || function(){ return true; };
		var args = paramsObj.args || {};
		var message = paramsObj.failureMessage || "Not valid!";
	    if(!against(value, args)) Validate.fail(message);
	    return true;
	  },
	  
	  
	
    /**
     *	validates whatever it is you pass in, and handles the validation error for you so it gives a nice true or false reply
     *
     *	@var validationFunction {Function} - validation function to be used (ie Validation.validatePresence )
     *	@var value {mixed} - value to be checked if true or not (usually a boolean from the checked value of a checkbox)
     *	@var validationParamsObj {Object} - parameters for doing the validation, if wanted or necessary
     */
    now: function(validationFunction, value, validationParamsObj){
      	if(!validationFunction) throw new Error("Validate::now - Validation function must be provided!");
    	var isValid = true;
        try{    
    		validationFunction(value, validationParamsObj || {});
    	} catch(error) {
    		if(error instanceof Validate.Error){
    			isValid =  false;
    		}else{
    		 	throw error;
    		}
    	}finally{ 
            return isValid 
        }
    },
    
    /**
     * shortcut for failing throwing a validation error
     *
     *	@var errorMessage {String} - message to display
     */
    fail: function(errorMessage){
            throw new Validate.Error(errorMessage);
    },
    
    Error: function(errorMessage){
    	this.message = errorMessage;
    	this.name = 'ValidationError';
    }

}
//////////////////////////////////////////////////////////////////////////
// LiveValidation 1.3 (standalone version)
// Copyright (c) 2007-2008 Alec Hill (www.livevalidation.com)
// LiveValidation is licensed under the terms of the MIT License


// Custom email validation fuctions for SendLove
function SLEmail(B,C){if(B)B=B.replace(/^\s+|\s+$/g,""); Validate.Email(B,C); }
function SLEmail2(B,C){if(B && B.replace(/^\s+|\s+$/g,"").match(/^[\w\d]+ \([\w\d._%-]+@[\w\d.-]+\.[\w]{2,4}\)$/)) return true; return Validate.Email(B,C); }

var LiveValidation=function(B,A){
        this.initialize(B,A);};
        LiveValidation.VERSION="1.3 standalone";
        LiveValidation.TEXTAREA=1;
        LiveValidation.TEXT=2;
        LiveValidation.PASSWORD=3;
        LiveValidation.CHECKBOX=4;
        LiveValidation.SELECT=5;
        LiveValidation.FILE=6;
        LiveValidation.massValidate=function(C){
                var D=true;
                for(var B=0,A=C.length;B<A;++B){
                        var E=C[B].validate();if(D){D=E;}
                        }return D;
                        };
                        LiveValidation.prototype={
                                validClass:"LV_valid",invalidClass:"LV_invalid",messageClass:"LV_validation_message",validFieldClass:"LV_valid_field",invalidFieldClass:"LV_invalid_field",initialize:function(D,C){var A=this;
                                if(!D){throw new Error("LiveValidation::initialize - No element reference or element id has been provided!");}
                                this.element=D.nodeName?D:document.getElementById(D);if(!this.element){throw new Error("LiveValidation::initialize - No element with reference or id of '"+D+"' exists!");}
                                this.validations=[];this.elementType=this.getElementType();
                                this.form=this.element.form;var B=C||{};
                                this.validMessage=B.validMessage||"";
                                var E=B.insertAfterWhatNode||this.element;
                                this.insertAfterWhatNode=E.nodeType?E:document.getElementById(E);
                                this.onValid=B.onValid||function(){this.insertMessage(this.createMessageSpan());
                                this.addFieldClass();};
                                this.onInvalid=B.onInvalid||function(){this.insertMessage(this.createMessageSpan());
                                this.addFieldClass();};
                                this.onlyOnBlur=B.onlyOnBlur||false;
                                this.wait=B.wait||0;this.onlyOnSubmit=B.onlyOnSubmit||false;
                                if(this.form){this.formObj=LiveValidationForm.getInstance(this.form);
                                this.formObj.addField(this);}
                                this.oldOnFocus=this.element.onfocus||function(){};
                                this.oldOnBlur=this.element.onblur||function(){};this.oldOnClick=this.element.onclick||function(){};
                                this.oldOnChange=this.element.onchange||function(){};
                                this.oldOnKeyup=this.element.onkeyup||function(){};
                                this.element.onfocus=function(F){A.doOnFocus(F);
                                return A.oldOnFocus.call(this,F);};
                                if(!this.onlyOnSubmit){switch(this.elementType){case LiveValidation.CHECKBOX:this.element.onclick=function(F){A.validate();
                                return A.oldOnClick.call(this,F);};
                                case LiveValidation.SELECT:case LiveValidation.FILE:this.element.onchange=function(F){A.validate();
                                return A.oldOnChange.call(this,F);};
                                break;
                                default:if(!this.onlyOnBlur){this.element.onkeyup=function(F){A.deferValidation();
                                return A.oldOnKeyup.call(this,F);};}this.element.onblur=function(F){A.doOnBlur(F);
                                return A.oldOnBlur.call(this,F);};}}},destroy:function(){if(this.formObj){
                                        this.formObj.removeField(this);
                                        this.formObj.destroy();}this.element.onfocus=this.oldOnFocus;if(!this.onlyOnSubmit){switch(this.elementType){case LiveValidation.CHECKBOX:this.element.onclick=this.oldOnClick;
                                        case LiveValidation.SELECT:case LiveValidation.FILE:this.element.onchange=this.oldOnChange;break;
                                        default:if(!this.onlyOnBlur){this.element.onkeyup=this.oldOnKeyup;}this.element.onblur=this.oldOnBlur;}}this.validations=[];
                                        this.removeMessageAndFieldClass();},add:function(A,B){this.validations.push({type:A,params:B||{}});
                                        return this;},remove:function(B,D){var E=false;for(var C=0,A=this.validations.length;C<A;C++){if(this.validations[C].type==B){if(this.validations[C].params==D){E=true;break;}}}
                                        if(E){this.validations.splice(C,1);}return this;},deferValidation:function(B){if(this.wait>=300){this.removeMessageAndFieldClass();}
                                        var A=this;if(this.timeout){clearTimeout(A.timeout);}this.timeout=setTimeout(function(){A.validate();},A.wait);},doOnBlur:function(A){this.focused=false;this.validate(A);},doOnFocus:function(A){this.focused=true;this.removeMessageAndFieldClass();},getElementType:function(){switch(true){case (this.element.nodeName.toUpperCase()=="TEXTAREA"):return LiveValidation.TEXTAREA;case (this.element.nodeName.toUpperCase()=="INPUT"&&this.element.type.toUpperCase()=="TEXT"):return LiveValidation.TEXT;case (this.element.nodeName.toUpperCase()=="INPUT"&&this.element.type.toUpperCase()=="PASSWORD"):return LiveValidation.PASSWORD;case (this.element.nodeName.toUpperCase()=="INPUT"&&this.element.type.toUpperCase()=="CHECKBOX"):return LiveValidation.CHECKBOX;case (this.element.nodeName.toUpperCase()=="INPUT"&&this.element.type.toUpperCase()=="FILE"):return LiveValidation.FILE;case (this.element.nodeName.toUpperCase()=="SELECT"):return LiveValidation.SELECT;case (this.element.nodeName.toUpperCase()=="INPUT"):throw new Error("LiveValidation::getElementType - Cannot use LiveValidation on an "+this.element.type+" input!");default:throw new Error("LiveValidation::getElementType - Element must be an input, select, or textarea!");}},doValidations:function(){this.validationFailed=false;for(var C=0,A=this.validations.length;C<A;++C){var B=this.validations[C];switch(B.type){
        case Validate.Presence:case Validate.Confirmation:case Validate.Acceptance:this.displayMessageWhenEmpty=true;this.validationFailed=!this.validateElement(B.type,B.params);break;default:this.validationFailed=!this.validateElement(B.type,B.params);break;}if(this.validationFailed){return false;}}this.message=this.validMessage;return true;},validateElement:function(A,C){var D=(this.elementType==LiveValidation.SELECT)?this.element.options[this.element.selectedIndex].value:this.element.value;if(A==Validate.Acceptance){if(this.elementType!=LiveValidation.CHECKBOX){throw new Error("LiveValidation::validateElement - Element to validate acceptance must be a checkbox!");}D=this.element.checked;}var E=true;try{A(D,C);}catch(B){if(B instanceof Validate.Error){if(D!==""||(D===""&&this.displayMessageWhenEmpty)){this.validationFailed=true;this.message=B.message;E=false;}}else{throw B;}}finally{return E;}},validate:function(){if(!this.element.disabled){var A=this.doValidations();if(A){this.onValid();return true;}else{this.onInvalid();return false;}}else{return true;}},enable:function(){this.element.disabled=false;return this;},disable:function(){this.element.disabled=true;this.removeMessageAndFieldClass();return this;},createMessageSpan:function(){var A=document.createElement("span");var B=document.createTextNode(this.message);A.appendChild(B);return A;},insertMessage:function(B){this.removeMessage();if((this.displayMessageWhenEmpty&&(this.elementType==LiveValidation.CHECKBOX||this.element.value==""))||this.element.value!=""){var A=this.validationFailed?this.invalidClass:this.validClass;B.className+=" "+this.messageClass+" "+A;if(this.insertAfterWhatNode.nextSibling){this.insertAfterWhatNode.parentNode.insertBefore(B,this.insertAfterWhatNode.nextSibling);}else{this.insertAfterWhatNode.parentNode.appendChild(B);}}},addFieldClass:function(){this.removeFieldClass();if(!this.validationFailed){if(this.displayMessageWhenEmpty||this.element.value!=""){if(this.element.className.indexOf(this.validFieldClass)==-1){this.element.className+=" "+this.validFieldClass;}}}else{if(this.element.className.indexOf(this.invalidFieldClass)==-1){this.element.className+=" "+this.invalidFieldClass;}}},removeMessage:function(){var A;var B=this.insertAfterWhatNode;while(B.nextSibling){if(B.nextSibling.nodeType===1){A=B.nextSibling;break;}B=B.nextSibling;}if(A&&A.className.indexOf(this.messageClass)!=-1){this.insertAfterWhatNode.parentNode.removeChild(A);}},removeFieldClass:function(){if(this.element.className.indexOf(this.invalidFieldClass)!=-1){this.element.className=this.element.className.split(this.invalidFieldClass).join("");}if(this.element.className.indexOf(this.validFieldClass)!=-1){this.element.className=this.element.className.split(this.validFieldClass).join(" ");}},removeMessageAndFieldClass:function(){this.removeMessage();this.removeFieldClass();}};var LiveValidationForm=function(A){this.initialize(A);};LiveValidationForm.instances={};LiveValidationForm.getInstance=function(A){var B=Math.random()*Math.random();if(!A.id){A.id="formId_"+B.toString().replace(/\./,"")+new Date().valueOf();}if(!LiveValidationForm.instances[A.id]){LiveValidationForm.instances[A.id]=new LiveValidationForm(A);}return LiveValidationForm.instances[A.id];};LiveValidationForm.prototype={initialize:function(B){this.name=B.id;this.element=B;this.fields=[];this.oldOnSubmit=this.element.onsubmit||function(){};var A=this;this.element.onsubmit=function(C){return(LiveValidation.massValidate(A.fields))?A.oldOnSubmit.call(this,C||window.event)!==false:false;};},addField:function(A){this.fields.push(A);},removeField:function(C){var D=[];for(var B=0,A=this.fields.length;B<A;B++){if(this.fields[B]!==C){D.push(this.fields[B]);}}this.fields=D;},destroy:function(A){if(this.fields.length!=0&&!A){return false;}this.element.onsubmit=this.oldOnSubmit;LiveValidationForm.instances[this.name]=null;return true;}};var Validate={Presence:function(B,C){
                var C=C||{};
                var A=C.failureMessage||"Can't be empty!";
               
                if(B===""||B===null||B===undefined){
                        Validate.fail(A);
                        }
                        return true;
                        },Numericality:function(J,E){
                                var A=J;var J=Number(J);var E=E||{};
                                var F=((E.minimum)||(E.minimum==0))?E.minimum:null;
                                var C=((E.maximum)||(E.maximum==0))?E.maximum:null;
                                var D=((E.is)||(E.is==0))?E.is:null;
                                var G=E.notANumberMessage||"Must be a number!";
                                var H=E.notAnIntegerMessage||"Must be an integer!";
                                var I=E.wrongNumberMessage||"Must be "+D+"!";
                                var B=E.tooLowMessage||"Must not be less than "+F+"!";
                                var K=E.tooHighMessage||"Must not be more than "+C+"!";
                                if(!isFinite(J)){Validate.fail(G);}
                                if(E.onlyInteger&&(/\.0+$|\.$/.test(String(A))||J!=parseInt(J))){Validate.fail(H);}
switch(true){case (D!==null):if(J!=Number(D)){
        Validate.fail(I);
        }
        break;case (F!==null&&C!==null):Validate.Numericality(J,{tooLowMessage:B,minimum:F});
        Validate.Numericality(J,{tooHighMessage:K,maximum:C});
        break;case (F!==null):if(J<Number(F)){Validate.fail(B);}
        break;case (C!==null):if(J>Number(C)){Validate.fail(K);}
        break;}return true;},Format:function(C,E){var C=String(C);
        var E=E||{};
        var A=E.failureMessage||"Not valid!";
        var B=E.pattern||/./;
        var D=E.negate||false;
        if(!D&&!B.test(C)){Validate.fail(A);}
        if(D&&B.test(C)){Validate.fail(A);}
        return true;},Email:function(B,C){
        var C=C||{};
        var A=C.failureMessage||"Must be a valid email address!";
Validate.Format(B,{
                                failureMessage:A,pattern:/^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i});return true;
}
,Length:function(F,G){
        var F=String(F);var G=G||{};
        var E=((G.minimum)||(G.minimum==0))?G.minimum:null;
        var H=((G.maximum)||(G.maximum==0))?G.maximum:null;var C=((G.is)||(G.is==0))?G.is:null;
        var A=G.wrongLengthMessage||"Must be "+C+" characters long!";var B=G.tooShortMessage||"Must not be less than "+E+" characters long!";var D=G.tooLongMessage||"Must not be more than "+H+" characters long!";switch(true){case (C!==null):if(F.length!=Number(C)){Validate.fail(A);}break;case (E!==null&&H!==null):Validate.Length(F,{tooShortMessage:B,minimum:E});Validate.Length(F,{tooLongMessage:D,maximum:H});break;case (E!==null):if(F.length<Number(E)){Validate.fail(B);}break;case (H!==null):if(F.length>Number(H)){Validate.fail(D);}break;default:throw new Error("Validate::Length - Length(s) to validate against must be provided!");}return true;},Inclusion:function(H,F){var F=F||{};var K=F.failureMessage||"Must be included in the list!";var G=(F.caseSensitive===false)?false:true;if(F.allowNull&&H==null){return true;}if(!F.allowNull&&H==null){Validate.fail(K);}var D=F.within||[];if(!G){var A=[];for(var C=0,B=D.length;C<B;++C){var I=D[C];if(typeof I=="string"){I=I.toLowerCase();}A.push(I);}D=A;if(typeof H=="string"){H=H.toLowerCase();}}var J=false;for(var E=0,B=D.length;E<B;++E){if(D[E]==H){J=true;}if(F.partialMatch){if(H.indexOf(D[E])!=-1){J=true;}}}if((!F.negate&&!J)||(F.negate&&J)){Validate.fail(K);}return true;},Exclusion:function(A,B){var B=B||{};B.failureMessage=B.failureMessage||"Must not be included in the list!";B.negate=true;Validate.Inclusion(A,B);return true;},Confirmation:function(C,D){if(!D.match){throw new Error("Validate::Confirmation - Error validating confirmation: Id of element to match must be provided!");}var D=D||{};var B=D.failureMessage||"Does not match!";var A=D.match.nodeName?D.match:document.getElementById(D.match);if(!A){throw new Error("Validate::Confirmation - There is no reference with name of, or element with id of '"+D.match+"'!");}if(C!=A.value){Validate.fail(B);}return true;},Acceptance:function(B,C){var C=C||{};var A=C.failureMessage||"Must be accepted!";if(!B){Validate.fail(A);}return true;},
Custom1: function(value, paramsObj){
		if(!paramsObj.match) throw new Error("Validate::Confirmation - Error validating confirmation: Id of element to match must be provided!");
    	var paramsObj = paramsObj || {};
    	var message = paramsObj.failureMessage || "Does not match!";
    	var match = paramsObj.match.nodeName ? paramsObj.match : document.getElementById(paramsObj.match);
    	if(!match) 
		Validate.fail(message);
		//throw new Error("Validate::Confirmation - There is no reference with name of, or element with id of '" + paramsObj.match + "'!");
    	if(value != match.value){ 
    	  	Validate.fail(message);
    	}
    	return true;
	  },
		Custom:function(D,E){var E=E||{};var B=E.against||function(){return true;};var A=E.args||{};var C=E.failureMessage||"Not valid!";if(!B(D,A)){Validate.fail(C);}return true;},now:function(A,D,C){if(!A){throw new Error("Validate::now - Validation function must be provided!");}var E=true;try{A(D,C||{});}catch(B){if(B instanceof Validate.Error){E=false;}else{throw B;}}finally{return E;}},fail:function(A){throw new Validate.Error(A);},Error:function(A){this.message=A;this.name="ValidationError";}};
 