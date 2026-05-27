enum WorkflowStep {
  noWorkOrder,
  workOrderFound,
  preTripMedical,
  preTripMedicalRejected,
  preTripTechnical,
  preTripTechnicalRejected,
  initialPrint,
  startShift,
  activeShift,
  postTripMedical,
  postTripMedicalRejected,
  postTripTechnical,
  postTripTechnicalRejected,
  finalPrint,
  closeShift,
  closed,
  cancelled;

  static WorkflowStep fromApi(String? value) {
    return switch (value) {
      'no_work_order' => WorkflowStep.noWorkOrder,
      'work_order_found' => WorkflowStep.workOrderFound,
      'pre_trip_medical' => WorkflowStep.preTripMedical,
      'pre_trip_medical_rejected' => WorkflowStep.preTripMedicalRejected,
      'pre_trip_technical' => WorkflowStep.preTripTechnical,
      'pre_trip_technical_rejected' => WorkflowStep.preTripTechnicalRejected,
      'initial_print' => WorkflowStep.initialPrint,
      'start_shift' => WorkflowStep.startShift,
      'active_shift' => WorkflowStep.activeShift,
      'post_trip_medical' => WorkflowStep.postTripMedical,
      'post_trip_medical_rejected' => WorkflowStep.postTripMedicalRejected,
      'post_trip_technical' => WorkflowStep.postTripTechnical,
      'post_trip_technical_rejected' => WorkflowStep.postTripTechnicalRejected,
      'final_print' => WorkflowStep.finalPrint,
      'close_shift' => WorkflowStep.closeShift,
      'closed' => WorkflowStep.closed,
      'cancelled' => WorkflowStep.cancelled,
      _ => WorkflowStep.noWorkOrder,
    };
  }
}

