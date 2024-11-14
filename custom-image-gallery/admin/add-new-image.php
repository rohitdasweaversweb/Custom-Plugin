<div class="wrap sbmt-fm">
    <form id="image-upload-form" class="frm" method="post">
        <button type="button" id="select-images-button" class="button slc-btn">Select Images</button>
        
        <div id="selected-images" class="s-img"></div>
        
        <!-- Hidden input to store image URLs -->
        <input type="hidden" id="image_urls" name="image_urls">
        
        <!-- Title input field -->
        <input type="text" id="tittle" name="tittle" placeholder="Gallery Title" required>
        
        <!-- Description input field -->
        <textarea name="description" id="description" placeholder="Gallery Description" required rows="4" cols="50"></textarea>
        
        <!-- Submit button -->
        <input type="submit" name="submit" value="Save Gallery">
        
        <!-- Status message container -->
        <div id="status-message"></div>
    </form>
</div>
