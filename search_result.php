<?php
if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Authors</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Uploaded By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['authors']) ?></td>
                        <td><?= htmlspecialchars($row['department']) ?></td>
                        <td><?= htmlspecialchars($row['year_published']) ?></td>
                        <td><?= htmlspecialchars($row['uploader']) ?></td>
                        <td>
                            <!-- Buttons for abstract, PDF, edit/delete as before -->
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?= generate_pagination_links($page, $total_pages, 'search.php', [
                    'keyword' => $keyword,
                    'department' => $department,
                    'year' => $year,
                    'author' => $author,
                    'sort_by' => $sort_by
                ]) ?>
            </ul>
        </nav>
    <?php endif; ?>

<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No research papers found.
    </div>
<?php endif; ?>
