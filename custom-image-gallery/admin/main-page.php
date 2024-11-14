<div class="wrap gallery-tbl">
    <h2>Gallery Images</h2>
    <div id="sts-message"></div>
    <table id="gallery-table" border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Image</th>
                <th>Image Tittle</th>
                <th>Image Description</th>
                <th>Actions</th>
                <th>Shortcode Section</th>
            </tr>
        </thead>
        <tbody>
            <!-- Gallery images will be dynamically inserted here -->
        </tbody>
    </table>

    <div id="pagination" style="margin-top: 20px; text-align: center;">
        <!-- Pagination buttons will be dynamically inserted here -->
    </div>
    
</div>

<!-- Edit Modal -->
<!-- Bootstrap Modal -->
<!-- Edit Modal -->

<div id="editModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="edit-form">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Gallery Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-id" name="edit-id">
                    <div class="form-group">
                        <label for="edit-title">Gallery Title</label>
                        <input type="text" id="edit-title" name="edit-title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-description">Gallery Description</label>
                        <textarea id="edit-description" name="edit-description" class="form-control" required></textarea>
                    </div>
                    <div id="edit-image-preview"></div>
                    <input type="hidden" id="edit-image-url" name="edit-image-url">
                    <button type="button" id="select-images" class="btn btn-primary">Select Images</button>
                    <div id="edit-status-message"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
