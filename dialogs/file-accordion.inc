<h3>Images (<span class="imageCount"><#= images.length #></span>)</h3>
<div class="fileimagecontainer">
<# if (images.length > 0) { #>
    <# for(var i=0; i < images.length; i++) {
    var image = images[i];
    #>
    <div class="filesIcon">
        <a class="attachment" href="<#= image.url #>"><img width="75px" height="75px" src="<#= image.icon #>" /></a>
    </div>
    <div class="filesDescription">
        <a class="removeAttachment" id="fileRemoveAttachment_<#= image.fileid #>" href="javascript:;">Delete</a>
        <h3 class="edittext" id="fileTitle_<#= image.fileid #>"><#= image.title #></h3>
        <p class="edittextarea" id="fileDesc_<#= image.fileid #>"><#= image.description #></p>
    </div>
    <div class="clear"></div>
    <# } #>
<# } #>
</div>
<h3>Documents (<span class="documentCount"><#= documents.length #></span>)</h3>
<div class="filedocumentcontainer">
<# if (documents.length > 0) { #>
    <# for(var i=0; i < documents.length; i++) {
    var doc = documents[i];
    #>
    <div class="filesIcon">
        <a class="docs" href="<#= doc.url #>" data-fileid="<#= doc.fileid #>" target="_blank"><img width="32px" height="32px" src="<#= doc.icon #>" /></a>
    </div>
    <div class="documents filesDescription">
        <a class="removeAttachment" id="fileRemoveAttachment_<#= doc.fileid #>" href="javascript:;">Delete</a>
        <h3 class="edittext" id="fileTitle_<#= doc.fileid #>"><#= doc.title #></h3>
        <p class="edittextarea" id="fileDesc_<#= doc.fileid #>"><#= doc.description #></p>
    </div>
    <div class="clear"></div>
    <# } #>
<# } #>
</div>
