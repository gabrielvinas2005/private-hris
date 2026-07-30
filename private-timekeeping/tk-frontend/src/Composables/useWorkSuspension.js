// Composable for Work Suspension Management
export function useWorkSuspension(api) {
  const base = {
    list: () => api.get('/work-cancellations'),
    get: (id) => api.get(`/work-cancellations/${id}`),
    create: () => api.get('/work-cancellations/create'),
    add: (id = 0) => api.get(`/work-cancellations/add/${id}`),
    edit: (id) => api.get(`/work-cancellations/edit/${id}`),
    store: (id, data) => api.post(`/work-cancellations/store/${id}`, data),
    update: (id, data) => api.put(`/work-cancellations/update/${id}`, data),
    delete: (id) => api.delete(`/work-cancellations/destroy/${id}`)
  }

  return {
    fetchList: base.list,
    fetchItem: base.get,
    fetchCreateForm: base.create,
    fetchAddForm: base.add,
    fetchEditForm: base.edit,
    createItem: base.store,
    updateItem: base.update,
    deleteItem: base.delete,
  }
}
