(function () {
  "use strict";

  function detectBasePath() {
    var path = window.location.pathname || "/";
    var parts = path.split("/").filter(Boolean);
    var appFolders = {
      public: true,
      dashboard: true,
      admin: true,
      client: true,
      worker: true,
      api: true
    };

    while (parts.length > 0 && appFolders[parts[parts.length - 1]]) {
      parts.pop();
    }

    return parts.length ? "/" + parts.join("/") : "";
  }

  var BASE_PATH = detectBasePath();

  function ensureDiscoveryMeta() {
    if (!document || !document.head) {
      return;
    }

    if (!document.head.querySelector('meta[name="webmcp-manifest"]')) {
      var m1 = document.createElement("meta");
      m1.setAttribute("name", "webmcp-manifest");
      m1.setAttribute("content", BASE_PATH + "/.well-known/webmcp.json");
      document.head.appendChild(m1);
    }

    if (!document.head.querySelector('meta[name="mcp-compatible"]')) {
      var m2 = document.createElement("meta");
      m2.setAttribute("name", "mcp-compatible");
      m2.setAttribute("content", "true");
      document.head.appendChild(m2);
    }
  }

  function buildApiUrl(path, query) {
    var url = BASE_PATH + path;
    if (!query) {
      return url;
    }

    var params = new URLSearchParams();
    Object.keys(query).forEach(function (key) {
      var value = query[key];
      if (value !== undefined && value !== null && value !== "") {
        params.set(key, String(value));
      }
    });

    var queryString = params.toString();
    return queryString ? url + "?" + queryString : url;
  }

  async function fetchJson(url, options) {
    var response = await fetch(url, options || {});
    var data = {};

    try {
      data = await response.json();
    } catch (err) {
      data = { error: "Invalid JSON response" };
    }

    if (!response.ok) {
      return {
        success: false,
        error: data && data.error ? data.error : "Request failed",
        status: response.status
      };
    }

    return data;
  }

  function registerTools() {
    if (!("modelContext" in navigator) || !navigator.modelContext || typeof navigator.modelContext.registerTool !== "function") {
      return;
    }

    ensureDiscoveryMeta();

    navigator.modelContext.registerTool({
      name: "get_portfolio_projects",
      description: "List all architecture projects in Ripal Design's portfolio, optionally filtered by category (residential, commercial, interior, urban).",
      parameters: {
        type: "object",
        properties: {
          category: {
            type: "string",
            enum: ["residential", "commercial", "interior", "urban", "all"],
            default: "all"
          }
        }
      },
      handler: async function (params) {
        var category = params && params.category ? params.category : "all";
        return fetchJson(buildApiUrl("/api/projects.php", { category: category }));
      }
    });

    navigator.modelContext.registerTool({
      name: "get_project_detail",
      description: "Get full details of a specific architecture project including images, materials used, client brief, and completion date.",
      parameters: {
        type: "object",
        properties: {
          project_id: { type: "string" }
        },
        required: ["project_id"]
      },
      handler: async function (params) {
        var projectId = params && params.project_id ? params.project_id : "";
        return fetchJson(buildApiUrl("/api/projects.php", { id: projectId }));
      }
    });

    navigator.modelContext.registerTool({
      name: "get_project_team_members",
      description: "List workers or employees assigned to a specific project.",
      parameters: {
        type: "object",
        properties: {
          project_id: { type: "string" }
        },
        required: ["project_id"]
      },
      handler: async function (params) {
        var projectId = params && params.project_id ? params.project_id : "";
        return fetchJson(buildApiUrl("/api/project-team.php", { id: projectId }));
      }
    });

    navigator.modelContext.registerTool({
      name: "get_role_actions",
      description: "List role-based actions the currently logged-in user is allowed to execute through AI.",
      parameters: {
        type: "object",
        properties: {}
      },
      handler: async function () {
        return fetchJson(buildApiUrl("/api/role-actions.php"));
      }
    });

    navigator.modelContext.registerTool({
      name: "execute_role_action",
      description: "Execute an allowed non-delete action as the currently logged-in user. Requires explicit user confirmation.",
      readOnly: false,
      parameters: {
        type: "object",
        properties: {
          action_key: { type: "string" },
          params: { type: "object" }
        },
        required: ["action_key"]
      },
      handler: async function (params) {
        var actionKey = params && params.action_key ? String(params.action_key) : "";
        var requestParams = params && params.params && typeof params.params === "object" ? params.params : {};

        await navigator.modelContext.requestUserInteraction?.({
          reason: "Confirm executing action: " + actionKey
        });

        return fetchJson(buildApiUrl("/api/execute-role-action.php"), {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
          },
          body: JSON.stringify({
            action_key: actionKey,
            params: requestParams,
            confirmed: true
          })
        });
      }
    });

    navigator.modelContext.registerTool({
      name: "get_firm_info",
      description: "Get information about the architecture firm including services offered, team members, awards, and contact details.",
      parameters: {
        type: "object",
        properties: {}
      },
      handler: async function () {
        return fetchJson(buildApiUrl("/api/firm.php"));
      }
    });

    navigator.modelContext.registerTool({
      name: "search_projects",
      description: "Search the portfolio by keyword, location, year range, or building type.",
      parameters: {
        type: "object",
        properties: {
          query: { type: "string" },
          location: { type: "string" },
          year_from: { type: "number" },
          year_to: { type: "number" },
          type: { type: "string" }
        }
      },
      handler: async function (params) {
        var requestParams = {
          q: params && params.query ? params.query : "",
          location: params && params.location ? params.location : "",
          year_from: params && params.year_from ? params.year_from : "",
          year_to: params && params.year_to ? params.year_to : "",
          type: params && params.type ? params.type : ""
        };

        return fetchJson(buildApiUrl("/api/search.php", requestParams));
      }
    });

    navigator.modelContext.registerTool({
      name: "request_consultation",
      description: "Submit a consultation request to the firm on behalf of the user. Requires explicit user confirmation before submitting.",
      readOnly: false,
      parameters: {
        type: "object",
        properties: {
          name: { type: "string" },
          email: { type: "string" },
          project_type: { type: "string" },
          message: { type: "string" },
          preferred_date: { type: "string" }
        },
        required: ["name", "email", "project_type", "message"]
      },
      handler: async function (params) {
        await navigator.modelContext.requestUserInteraction?.({
          reason: "Confirm sending consultation request to Ripal Design"
        });

        return fetchJson(buildApiUrl("/api/consultation.php"), {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
          },
          body: JSON.stringify(params || {})
        });
      }
    });

    navigator.modelContext.registerTool({
      name: "get_available_consultation_slots",
      description: "Get available consultation appointment slots for the next 30 days.",
      parameters: {
        type: "object",
        properties: {
          month: { type: "string" }
        }
      },
      handler: async function (params) {
        var month = params && params.month ? params.month : "";
        return fetchJson(buildApiUrl("/api/slots.php", { month: month }));
      }
    });
  }

  // Polyfill for browsers without native WebMCP support
  if (!("modelContext" in navigator)) {
    var script = document.createElement("script");
    script.src = "https://unpkg.com/@mcp-b/webmcp-polyfill/dist/index.js";
    script.onload = function () {
      registerTools();
    };
    document.head.appendChild(script);
  } else {
    registerTools();
  }
})();
