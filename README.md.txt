# C5: Round Robin vs Preemptive SJF (SRTF) Simulator

## Team Members
- [Name 1] - [ID 1] - Contribution: Scheduling Logic
- [Name 2] - [ID 2] - Contribution: GUI & Interface
- [Name 3] - [ID 3] - Contribution: Metrics Calculation
- [Name 4] - [ID 4] - Contribution: Validation & Security
- [Name 5] - [ID 5] - Contribution: Auto-Analysis Engine
- [Name 6] - [ID 6] - Contribution: Documentation & GitHub
- [Name 7] - [ID 7] - Contribution: Test Scenarios

## Project Description
A comprehensive web-based Operating Systems simulator that compares the **Round Robin (RR)** algorithm with the **Preemptive Shortest Job First (SRTF)** algorithm. The system calculates Waiting Time (WT), Turnaround Time (TAT), Response Time (RT), CPU Utilization, and System Throughput.

## Assumptions & Limitations
Based on the project requirements and typical OS environment constraints:
- **Assumption 1:** The SJF algorithm is strictly **Preemptive** (Shortest Remaining Time First - SRTF).
- **Assumption 2:** Context switching time is assumed to be 0 ms to focus strictly on algorithm logic comparison.
- **Limitation 1:** The simulator currently assumes all burst times and arrival times are positive integers.

## Implementation Technology
- **Programming Language:** PHP (Backend Logic)
- **GUI Technology:** HTML5, CSS3, JavaScript, Chart.js

## How to Run the Project
1. Ensure you have a local PHP server installed (e.g., XAMPP).
2. Clone this repository into your server's root directory (e.g., `htdocs/os_c5_project`).
3. Start the Apache server.
4. Open your browser and navigate to: `http://localhost/os_c5_project/src/gui/index.php`
5. Use the GUI to input process data or load predefined scenarios, then click "Run Simulation".