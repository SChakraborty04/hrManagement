class DataTable {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        this.options = {
            rowsPerPage: 10,
            pageRange: 2,
            ...options
        };
        
        this.currentPage = 1;
        this.tbody = this.table.querySelector('tbody');
        this.allRows = Array.from(this.tbody.rows);
        this.totalRows = this.allRows.length;
        
        this.searchInput = document.getElementById(`searchInput${options.controlsSuffix}`);
        this.entriesSelect = document.getElementById(`entriesPerPage${options.controlsSuffix}`);
        this.paginationContainer = this.table.closest('.table-wrapper').querySelector('.pagination');
        this.tableInfo = this.table.closest('.table-wrapper').querySelector('.table-info');
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateTable();
    }

    setupEventListeners() {
        // Search functionality
        this.searchInput.addEventListener('input', () => {
            this.currentPage = 1;
            this.updateTable();
        });

        // Entries per page
        this.entriesSelect.addEventListener('change', () => {
            this.options.rowsPerPage = parseInt(this.entriesSelect.value);
            this.currentPage = 1;
            this.updateTable();
        });

        // Pagination clicks
        this.paginationContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('page-nav')) {
                const action = e.target.dataset.action;
                this.handlePageNavigation(action);
            } else if (e.target.classList.contains('page-number')) {
                this.currentPage = parseInt(e.target.dataset.page);
                this.updateTable();
            }
        });
    }

    handlePageNavigation(action) {
        const totalPages = Math.ceil(this.getFilteredRows().length / this.options.rowsPerPage);
        
        switch(action) {
            case 'first':
                this.currentPage = 1;
                break;
            case 'prev':
                this.currentPage = Math.max(1, this.currentPage - 1);
                break;
            case 'next':
                this.currentPage = Math.min(totalPages, this.currentPage + 1);
                break;
            case 'last':
                this.currentPage = totalPages;
                break;
        }
        
        this.updateTable();
    }

    getFilteredRows() {
        const searchTerm = this.searchInput.value.toLowerCase();
        return this.allRows.filter(row => {
            return Array.from(row.cells).some(cell => 
                cell.textContent.toLowerCase().includes(searchTerm)
            );
        });
    }

    updateTable() {
        const filteredRows = this.getFilteredRows();
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / this.options.rowsPerPage);
        
        // Ensure current page is valid
        this.currentPage = Math.min(Math.max(1, this.currentPage), totalPages);
        
        // Calculate visible rows
        const start = (this.currentPage - 1) * this.options.rowsPerPage;
        const end = Math.min(start + this.options.rowsPerPage, totalRows);
        
        // Clear and update table body
        this.tbody.innerHTML = '';
        filteredRows.slice(start, end).forEach(row => {
            this.tbody.appendChild(row.cloneNode(true));
        });
        
        // Update pagination
        this.updatePagination(totalPages);
        
        // Update table info
        this.updateTableInfo(start + 1, end, totalRows);
    }

    updatePagination(totalPages) {
        const pageNumbers = this.paginationContainer.querySelector('.page-numbers');
        pageNumbers.innerHTML = '';
        
        // Calculate page range
        let startPage = Math.max(1, this.currentPage - this.options.pageRange);
        let endPage = Math.min(totalPages, this.currentPage + this.options.pageRange);
        
        // Adjust range if at edges
        if (startPage === 1) {
            endPage = Math.min(totalPages, 1 + (this.options.pageRange * 2));
        }
        if (endPage === totalPages) {
            startPage = Math.max(1, totalPages - (this.options.pageRange * 2));
        }
        
        // Create page buttons
        for (let i = startPage; i <= endPage; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.className = `page-number${i === this.currentPage ? ' active' : ''}`;
            button.dataset.page = i;
            pageNumbers.appendChild(button);
        }
        
        // Update navigation button states
        this.paginationContainer.querySelectorAll('.page-nav').forEach(button => {
            const action = button.dataset.action;
            button.disabled = (action === 'first' || action === 'prev') ? this.currentPage === 1 
                          : (action === 'last' || action === 'next') ? this.currentPage === totalPages 
                          : false;
        });
    }

    updateTableInfo(start, end, total) {
        this.tableInfo.textContent = 
            `Showing ${start} to ${end} of ${total} entries${
                this.searchInput.value ? ` (filtered from ${this.totalRows} total entries)` : ''
            }`;
    }
}

// Initialize tables when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new DataTable('accessCodeTable', { controlsSuffix: '1' });
    new DataTable('traineeTable', { controlsSuffix: '2' });
    new DataTable('facultyTable', { controlsSuffix: '3' });
});

