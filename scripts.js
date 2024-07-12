document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.collapsible-content').forEach(content => content.style.display = 'none');

    loadUsers();
    loadActiveStudents();
    loadArchivedStudents();
});

function loadUsers() {
    fetch('./users/fetch_staff.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching users:', data.error);
                return;
            }

            const approvedUsersTableContainer = document.getElementById('approved-users-table-container');
            const waitingApprovalTableContainer = document.getElementById('waiting-approval-table-container');

            if (approvedUsersTableContainer && waitingApprovalTableContainer) {
                approvedUsersTableContainer.innerHTML = '';
                waitingApprovalTableContainer.innerHTML = '';
            } else {
                console.error('Table container elements not found');
                return;
            }

            const approvedTableData = data.filter(user => user.approved);
            const waitingApprovalTableData = data.filter(user => !user.approved);

            const approvedTable = new Tabulator(approvedUsersTableContainer, {
                data: approvedTableData,
                layout: "fitDataStretch",
                columns: [
                    { 
                        title: "Admin?", 
                        field: "is_admin", 
                        editor: "list", 
                        editorParams: {
                            values: [
                                {label: "Yes", value: 1},
                                {label: "No", value: 0}
                            ]
                        },
                        formatter: function(cell, formatterParams, onRendered) {
                            return cell.getValue() == 1 ? "Yes" : "No";
                        },
                        width: 100
                    },
                    { title: "First Name", field: "fname", editor: "input", widthGrow: 2 },
                    { title: "Last Name", field: "lname", editor: "input", widthGrow: 2 },
                    { title: "Subject Taught", field: "subject_taught", editor: "input", widthGrow: 2 },
                    {
                        title: "Delete", field: "teacher_id", formatter: function(cell, formatterParams, onRendered) {
                            return '<button class="delete-btn" onclick="deleteUser(' + cell.getValue() + ')">❌</button>';
                        },
                        width: 100
                    }
                ],
            });

            approvedTable.on("cellEdited", function(cell) {
                updateUser(cell.getRow().getData());
            });

            const waitingApprovalTable = new Tabulator(waitingApprovalTableContainer, {
                data: waitingApprovalTableData,
                layout: "fitDataStretch",
                columns: [
                    { 
                        title: "Is Admin", 
                        field: "is_admin", 
                        editor: "list", 
                        editorParams: {
                            values: [
                                {label: "Yes", value: 1},
                                {label: "No", value: 0}
                            ]
                        },
                        formatter: function(cell, formatterParams, onRendered) {
                            return cell.getValue() == 1 ? "Yes" : "No";
                        },
                        width: 100
                    },
                    { title: "First Name", field: "fname", editor: "input", widthGrow: 2 },
                    { title: "Last Name", field: "lname", editor: "input", widthGrow: 2 },
                    { title: "Subject Taught", field: "subject_taught", editor: "input", widthGrow: 2 },
                    {
                        title: "Approve?", field: "teacher_id", formatter: function(cell, formatterParams, onRendered) {
                            return '<button class="approve-btn" onclick="toggleApproval(' + cell.getValue() + ', 1)">✅</button>' +
                                   '<button class="delete-btn" onclick="deleteUser(' + cell.getValue() + ')">❌</button>';
                        },
                        width: 150
                    }
                ],
            });

            waitingApprovalTable.on("cellEdited", function(cell) {
                updateUser(cell.getRow().getData());
            });
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function loadActiveStudents() {
    fetch('./users/fetch_students.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching students:', data.error);
                return;
            }

            const activeStudentsTableContainer = document.getElementById('active-students-table-container');
            if (activeStudentsTableContainer) {
                activeStudentsTableContainer.innerHTML = '';
            } else {
                console.error('Table container element not found');
                return;
            }

            const activeStudentsTable = new Tabulator("#active-students-table-container", {
                data: data, // Use the fetched data
                layout: "fitDataStretch", // This makes sure columns use up the available space
                pagination: "local", // Enable local pagination
                paginationSize: 20, // Number of students per page
                paginationSizeSelector: [10, 20, 50, 100], // Page size options
                initialSort: [
                    {column: "last_name", dir: "asc"} // Sort by last name ascending
                ],
                columns: [
                    { title: "First Name", field: "first_name", widthGrow: 2 },
                    { title: "Last Name", field: "last_name", widthGrow: 2 },
                    { title: "Date of Birth", field: "date_of_birth", widthGrow: 2 },
                    { title: "Grade Level", field: "grade_level", widthGrow: 2 },
                    {
                        title: "Archive",
                        field: "student_id_new",
                        hozAlign: "center", // Centers the button horizontally
                        formatter: function(cell, formatterParams, onRendered) {
                            return '<button class="btn btn-archive" data-id="' + cell.getValue() + '">Archive</button>'; // Adding a class for styling
                        },
                        width: 100 // Set a fixed width for consistency
                    }
                ],
            });

            activeStudentsTable.on("cellEdited", function(cell) {
                updateStudent(cell.getRow().getData());
            });

            // Add event listener to the Archive buttons
            setTimeout(() => { // Delay to ensure DOM is updated
                document.querySelectorAll('.btn-archive').forEach(button => {
                    //console.log('Attaching event listener to archive button with data-id:', button.getAttribute('data-id')); // Debug log
                    button.addEventListener('click', function() {
                        const studentId = this.getAttribute('data-id');
                        archiveStudent(studentId);
                    });
                });
            }, 500); // Adjust delay if necessary
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function loadArchivedStudents() {
    fetch('./users/fetch_archived_students.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching students:', data.error);
                return;
            }

            const archivedStudentsTableContainer = document.getElementById('archived-students-table-container');
            if (archivedStudentsTableContainer) {
                archivedStudentsTableContainer.innerHTML = '';
            } else {
                console.error('Table container element not found');
                return;
            }

            const archivedStudentsTable = new Tabulator("#archived-students-table-container", {
                data: data, // Use the fetched data
                layout: "fitDataStretch", // This makes sure columns use up the available space
                pagination: "local", // Enable local pagination
                paginationSize: 20, // Number of students per page
                paginationSizeSelector: [10, 20, 50, 100], // Page size options
                initialSort: [
                    {column: "last_name", dir: "asc"} // Sort by last name ascending
                ],
                columns: [
                    { title: "First Name", field: "first_name", widthGrow: 2 },
                    { title: "Last Name", field: "last_name", widthGrow: 2 },
                    { title: "Date of Birth", field: "date_of_birth", widthGrow: 2 },
                    { title: "Grade Level", field: "grade_level", widthGrow: 2 },
                    {
                        title: "Activate", 
                        field: "student_id_new", 
                        hozAlign: "center", // Centers the button horizontally
                        formatter: function(cell, formatterParams, onRendered) {
                            return '<button class="btn btn-activate" data-id="' + cell.getValue() + '">Activate</button>';
                        },
                        width: 100 // Set a fixed width for consistency
                    }
                ],
            });

            archivedStudentsTable.on("cellEdited", function(cell) {
                // Update logic here
                //console.log('Cell edited', cell.getRow().getData());
            });

            // Add event listener to the Activate buttons
            setTimeout(() => { // Delay to ensure DOM is updated
                document.querySelectorAll('.btn-activate').forEach(button => {
                    button.addEventListener('click', function() {
                        const studentId = this.getAttribute('data-id');
                        activateStudent(studentId);
                    });
                });
            }, 500); // Adjust delay if necessary
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function activateStudent(studentId) {
    //console.log('Activating student with ID:', studentId);
    fetch('./users/activate_student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ studentId: studentId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            //console.log('Student activated successfully');
            // Reload both tables to reflect changes
            loadActiveStudents(); // Reload the active students table
            loadArchivedStudents(); // Reload the archived students table
        } else {
            console.error('Failed to activate student:', data.message);
        }
    })
    .catch(error => {
        console.error('Error activating student:', error);
    });
}

function toggleApproval(teacherId, newStatus) {
    fetch('./users/toggle_approval.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ teacher_id: teacherId, approved: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadUsers(); // Reload the users to reflect the change
        } else {
            console.error('Error updating approval status:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function deleteUser(teacherId) {
    if (!confirm('Are you sure you want to delete this user?')) return;

    fetch('./users/delete_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ teacher_id: teacherId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadUsers(); // Reload the users to reflect the deletion
        } else {
            console.error('Error deleting user:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function archiveStudent(studentId) {
    //console.log('Archiving student with ID:', studentId); // Debug log
    fetch('./users/archive_student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ student_id_new: studentId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            //console.log('Student archived successfully');
            loadActiveStudents(); // Reload the active students to reflect the change
            loadArchivedStudents(); // Reload the archived students to reflect the change
        } else {
            console.error('Error archiving student:', data.message);
        }
    })
    .catch(error => {
        console.error('Error archiving student:', error);
    });
}

function updateStudent(studentData) {
    fetch('./users/update_student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(studentData)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error updating student:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateUser(userData) {
    fetch('./users/update_staff.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error updating user:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const button = section.previousElementSibling.querySelector('.toggle-btn');

    if (section.style.display === "none" || section.style.display === "") {
        section.style.display = "block";
        button.textContent = "-";
    } else {
        section.style.display = "none";
        button.textContent = "+";
    }
}

function searchProgramUsers() {
    const searchTerm = document.getElementById('program-users-search').value;
    fetch('./users/fetch_users_by_program.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching users:', data.error);
                return;
            }

            const filteredData = data.filter(user => 
                user.fname.includes(searchTerm) || user.lname.includes(searchTerm) || user.email.includes(searchTerm)
            );

            const programUsersTableContainer = document.getElementById('program-users-table-container');
            if (programUsersTableContainer) {
                programUsersTableContainer.innerHTML = '';
            } else {
                console.error('Table container element not found');
                return;
            }

            const programUsersTable = new Tabulator(programUsersTableContainer, {
                data: filteredData,
                layout: "fitDataStretch",
                columns: [
                    { title: "First Name", field: "fname", widthGrow: 2 },
                    { title: "Last Name", field: "lname", widthGrow: 2 },
                    { title: "Email", field: "email", widthGrow: 2 },
                    {
                        title: "Add to School", 
                        field: "teacher_id", 
                        formatter: function(cell, formatterParams, onRendered) {
                            return '<button onclick="addUserToSchool(' + cell.getValue() + ')">Add</button>';
                        },
                        width: 150
                    }
                ],
            });
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function addUserToSchool(teacherId) {
    fetch('./users/add_user_to_school.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ teacher_id: teacherId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User added to school successfully');
            searchProgramUsers(); // Refresh the search results
            loadUsers(); // Refresh the Manage Users table
        } else {
            console.error('Error adding user to school:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
