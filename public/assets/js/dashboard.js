/**
 * Dashboard Analytics Module
 *
 * Handles real-time dashboard updates and chart rendering
 * Uses Vanilla JavaScript for optimal performance
 */

class DashboardManager {
  constructor() {
    this.charts = {};
    this.refreshInterval = 30000; // 30 seconds
    this.init();
  }

  /**
   * Initialize dashboard components
   */
  init() {
    this.initCharts();
    this.startAutoRefresh();
    this.initEventListeners();
  }

  /**
   * Initialize dashboard charts
   */
  initCharts() {
    this.createTicketStatusChart();
    this.createPriorityDistributionChart();
    this.createResolutionTimeChart();
  }

  /**
   * Create ticket status distribution chart
   */
  createTicketStatusChart() {
    const ctx = document.getElementById("ticketStatusChart");
    if (!ctx) return;

    this.charts.status = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["Open", "In Progress", "Pending", "Resolved", "Closed"],
        datasets: [
          {
            data: [0, 0, 0, 0, 0],
            backgroundColor: [
              "#3b82f6",
              "#f59e0b",
              "#8b5cf6",
              "#10b981",
              "#6b7280",
            ],
            borderWidth: 2,
            borderColor: "#ffffff",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
        },
      },
    });
  }

  /**
   * Fetch dashboard statistics via AJAX
   */
  async fetchDashboardStats() {
    try {
      const response = await fetch("/api/dashboard/stats", {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Authorization: `Bearer ${this.getAuthToken()}`,
        },
      });

      if (!response.ok) throw new Error("Failed to fetch stats");

      const data = await response.json();
      this.updateDashboard(data);
    } catch (error) {
      console.error("Dashboard update failed:", error);
      this.showNotification("Failed to update dashboard", "error");
    }
  }

  /**
   * Update dashboard with new data
   */
  updateDashboard(data) {
    // Update statistics cards
    this.updateStatCard("totalTickets", data.total_tickets);
    this.updateStatCard("openTickets", data.open_tickets);
    this.updateStatCard("resolvedToday", data.resolved_today);
    this.updateStatCard("avgResponseTime", data.avg_response_time + "h");

    // Update charts
    if (this.charts.status) {
      this.charts.status.data.datasets[0].data = [
        data.tickets_by_status.open,
        data.tickets_by_status.in_progress,
        data.tickets_by_status.pending,
        data.tickets_by_status.resolved,
        data.tickets_by_status.closed,
      ];
      this.charts.status.update();
    }
  }

  /**
   * Update individual statistic card
   */
  updateStatCard(id, value) {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = value;
      element.classList.add("animate-slide-in");
      setTimeout(() => element.classList.remove("animate-slide-in"), 500);
    }
  }

  /**
   * Start automatic dashboard refresh
   */
  startAutoRefresh() {
    setInterval(() => this.fetchDashboardStats(), this.refreshInterval);
  }

  /**
   * Show toast notification
   */
  showNotification(message, type = "info") {
    const toast = document.createElement("div");
    toast.className = `toast-helpdesk toast-${type} p-3 mb-2`;
    toast.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span>${message}</span>
                <button class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

    const container =
      document.querySelector(".toast-container") || this.createToastContainer();
    container.appendChild(toast);

    // Auto-remove after 5 seconds
    setTimeout(() => toast.remove(), 5000);
  }

  /**
   * Create toast notification container
   */
  createToastContainer() {
    const container = document.createElement("div");
    container.className = "toast-container";
    document.body.appendChild(container);
    return container;
  }
}
