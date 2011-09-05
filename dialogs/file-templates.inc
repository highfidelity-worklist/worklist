<script type="text/html" id="uploadImage">
    <div class="filesIcon">
        <a class="attachment" href="<#= url #>" data-fileid="<#= fileid #>" ><img width="75px" height="75px" src="<#= icon #>" /></a>
    </div>
    <div class="filesDescription">
        <h3 class="edittext" id="fileTitle_<#= fileid #>"><#= title #></h3>
        <p class="edittextarea" id="fileDesc_<#= fileid #>"><#= description #></p>
        <?php if (isset($worklist) && $worklist['status'] != 'DONE') : ?>
        <a class="removeAttachment" id="fileRemoveAttachment_<#= fileid #>" href="javascript:;">Remove attachment</a>
        <?php endif; ?>
    </div>
    <div class="clear"></div>
</script>
<script type="text/html" id="uploadDocument">
    <div class="filesIcon">
        <a class="docs" href="<#= url #>" data-fileid="<#= fileid #>" target="_blank"><img width="32px" height="32px" src="<#= icon #>" /></a>
    </div>
    <div class="documents filesDescription">
        <h3 class="edittext" id="fileTitle_<#= fileid #>"><#= title #></h3>
        <p class="edittextarea" id="fileDesc_<#= fileid #>"><#= description #></p>
        <?php if (isset($worklist) && $worklist['status'] != 'DONE') : ?>
        <a class="removeAttachment" id="fileRemoveAttachment_<#= fileid #>" href="javascript:;">Remove attachment</a>
        <?php endif; ?>
    </div>
    <div class="clear"></div>
</script>
